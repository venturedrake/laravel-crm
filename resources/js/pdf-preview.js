/*
 * pdf.js viewer used by the `crmPdfPreview` Alpine component.
 *
 * This module is imported dynamically (`await import('./pdf-preview')`) so
 * pdf.js and its worker stay out of the main bundle and are only fetched the
 * first time a user actually opens a preview. Vite emits it as its own chunk
 * under public/vendor/laravel-crm/assets/, which the package's whole-directory
 * `assets` publish rule already ships to host apps.
 */

/*
 * The `legacy/` build, not the default one, and deliberately so.
 *
 * pdf.js >= 4.3 calls Promise.withResolvers, which Safari only shipped in
 * 17.4, and it calls it on BOTH sides of the worker boundary. Polyfilling it
 * here would only ever patch the main thread — a Worker is a separate realm,
 * and a module worker cannot be wrapped in a blob that runs a shim first,
 * because its imports are hoisted above any code we could prepend. The legacy
 * build is the only version that carries its own core-js polyfill into both
 * realms. It costs ~13% over the default build, on a chunk that is only
 * fetched when someone actually opens a preview.
 */
import * as pdfjs from 'pdfjs-dist/legacy/build/pdf.mjs';
import workerUrl from 'pdfjs-dist/legacy/build/pdf.worker.min.mjs?url';

pdfjs.GlobalWorkerOptions.workerSrc = workerUrl;

/**
 * Ceiling on a canvas's backing store, in pixels.
 *
 * iOS Safari refuses to allocate past roughly 4096×4096 and hands back a
 * blank canvas rather than throwing, so nothing downstream can detect it. An
 * A4 page at 3× zoom on a 2× display is 3570×5052 = 18M, comfortably over.
 * Clamping trades sharpness at extreme zoom for a page that actually draws.
 */
const MAX_CANVAS_PIXELS = 4096 * 4096;

/**
 * Fetch and parse the document at `url`.
 *
 * The bytes are fetched by us rather than by pdf.js so the session cookie
 * rides along — the preview routes are authenticated and gated on
 * `can:view,<model>`, not public files.
 *
 * @param {string} url
 * @param {AbortSignal} [signal]
 * @returns {Promise<import('pdfjs-dist').PDFDocumentProxy>}
 */
export async function loadDocument(url, signal) {
    const response = await fetch(url, { credentials: 'same-origin', signal });

    if (!response.ok) {
        throw new Error(`Failed to load PDF (${response.status})`);
    }

    const data = await response.arrayBuffer();

    return pdfjs.getDocument({ data }).promise;
}

/**
 * The scale a document should open at to sit inside `availableWidth`.
 *
 * Capped at 1, so a page narrower than the panel opens at its native size
 * rather than being blown up to fill it, and floored at the zoom control's own
 * minimum so the displayed percentage is always one the buttons can return to.
 *
 * This exists because the canvases carry no max-width (see renderPages): a
 * page wider than the panel scrolls instead of shrinking, which is right for a
 * deliberate zoom and wrong for the first paint on a narrow screen, where an
 * A4 page at 100% would open already overflowing.
 *
 * @param {import('pdfjs-dist').PDFDocumentProxy} doc
 * @param {number} availableWidth
 * @returns {Promise<number>}
 */
export async function fitScale(doc, availableWidth) {
    const page = await doc.getPage(1);
    const { width } = page.getViewport({ scale: 1 });

    if (!width || !availableWidth) {
        return 1;
    }

    return Math.min(1, Math.max(0.5, Math.floor((availableWidth / width) * 100) / 100));
}

/**
 * Draw every page of `doc` into `container`, in document order, replacing
 * whatever was there before.
 *
 * Canvases are backed at devicePixelRatio × scale and then sized down in CSS,
 * so zooming stays crisp on HiDPI displays. `token` guards against a slower
 * earlier render (a rapid zoom, or a second document opened before the first
 * finished) writing its canvases in after a newer one: callers bump the token
 * and this render bails as soon as it notices it is stale.
 *
 * @param {import('pdfjs-dist').PDFDocumentProxy} doc
 * @param {HTMLElement} container
 * @param {number} scale
 * @param {() => boolean} [isStale]
 */
export async function renderPages(doc, container, scale, isStale = () => false) {
    const rendered = document.createDocumentFragment();

    for (let pageNumber = 1; pageNumber <= doc.numPages; pageNumber++) {
        if (isStale()) {
            return;
        }

        const page = await doc.getPage(pageNumber);
        const viewport = page.getViewport({ scale });
        const ratio = backingRatio(viewport);

        const canvas = document.createElement('canvas');
        canvas.width = Math.floor(viewport.width * ratio);
        canvas.height = Math.floor(viewport.height * ratio);
        canvas.style.width = `${Math.floor(viewport.width)}px`;
        canvas.style.height = `${Math.floor(viewport.height)}px`;

        // No max-width here, deliberately. Both CSS dimensions are set from
        // the viewport to hold the aspect ratio while the backing store is
        // oversampled; a `max-width: 100%` would cap the width alone and
        // leave the height untouched, so any zoom past fit-width would stop
        // widening the page and start stretching it vertically instead. A
        // page wider than the panel is meant to scroll, which is what the
        // min-w-fit wrapper in the drawer markup is for.
        canvas.className = 'crm-pdf-preview-page mx-auto mb-4 block bg-white shadow-lg';

        await page.render({
            canvasContext: canvas.getContext('2d'),
            viewport,
            transform: ratio === 1 ? null : [ratio, 0, 0, ratio, 0, 0],
        }).promise;

        page.cleanup();
        rendered.appendChild(canvas);
    }

    if (isStale()) {
        return;
    }

    // Swapped in one shot at the end: replacing the old canvases only once the
    // new set is complete avoids the blank flash a clear-then-draw would give
    // on every zoom step.
    container.replaceChildren(rendered);
}

/**
 * How much to oversample a page's canvas relative to its CSS size.
 *
 * devicePixelRatio normally, but stepped down whenever that would push the
 * backing store past MAX_CANVAS_PIXELS — a soft landing (a slightly fuzzy
 * page at 3× zoom) instead of the hard one (a blank page, silently).
 *
 * @param {{width: number, height: number}} viewport
 * @returns {number}
 */
function backingRatio(viewport) {
    const ratio = window.devicePixelRatio || 1;
    const area = viewport.width * viewport.height;

    if (area <= 0) {
        return ratio;
    }

    return Math.min(ratio, Math.sqrt(MAX_CANVAS_PIXELS / area));
}

/**
 * Release a document's worker-side resources. Safe to call more than once.
 *
 * @param {import('pdfjs-dist').PDFDocumentProxy|null} doc
 */
export function destroyDocument(doc) {
    if (doc) {
        doc.cleanup();
        doc.destroy();
    }
}
