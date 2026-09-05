import './bootstrap';
import Sortable from 'sortablejs';
import Chart from 'chart.js/auto';

window.Sortable = Sortable;
window.Chart = Chart;

import Picker from 'vanilla-picker';
window.Picker = Picker;

import tinymce from 'tinymce/tinymce';
import 'tinymce/models/dom';
import 'tinymce/themes/silver';
import 'tinymce/icons/default';
import 'tinymce/skins/ui/oxide/skin.js';
import 'tinymce/skins/ui/oxide-dark/skin.js';
import 'tinymce/skins/content/default/content.js';
import 'tinymce/skins/content/dark/content.js';
import 'tinymce/plugins/advlist';
import 'tinymce/plugins/autolink';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/link';
import 'tinymce/plugins/image';
import 'tinymce/plugins/table';
import 'tinymce/plugins/quickbars';
import 'tinymce/plugins/autoresize';
window.tinymce = tinymce;

/*
 * PDF preview slide-over.
 *
 * Registered as an Alpine component rather than a Livewire one so a single
 * instance can live in the layout, outside every Livewire root, and be driven
 * from anywhere — including index table rows — by a bubbling window event.
 * Livewire 3 already puts Alpine on window, same as Sortable/Chart/Picker above.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('crmPdfPreview', () => {
        /*
         * pdf.js state lives in this closure, NOT on the returned data object,
         * and that is load-bearing rather than stylistic.
         *
         * Alpine makes everything it is handed deeply reactive, and reactivity
         * is a Proxy. pdf.js brand-checks `#private` fields against the real
         * instance, so the first call that touches one through a proxy throws
         * "Cannot read from private field". The failure is unusually
         * misleading: `doc.numPages` reads `_pdfInfo` — a plain property — and
         * so survives the proxy, meaning the drawer renders its title and page
         * count correctly and only dies on the first getPage(), which routes
         * through WorkerTransport and its `#pagePromises`.
         *
         * Keeping these out of the data object keeps them raw. Anything the
         * markup needs to see (`ready`) is mirrored as a plain boolean below.
         */
        let doc = null;
        let viewer = null;
        let controller = null;
        // Bumped on every open/zoom/close; an in-flight render compares against
        // it and drops its canvases if a newer request has superseded it.
        let token = 0;

        return {
            isOpen: false,
            loading: false,
            error: null,
            url: null,
            downloadUrl: null,
            title: '',
            pages: 0,
            scale: 1.0,
            // Reactive stand-in for `doc`, which the markup must not touch.
            ready: false,

            /*
             * Announce that a drawer is mounted, so the preview buttons can
             * tell a listening layout from a silent one.
             *
             * `layouts/app.blade.php` is publishable, and a host that
             * published it before this feature existed has a copy with no
             * <x-crm-pdf-preview /> in it. The buttons would still render and
             * still dispatch, into nothing at all — a dead button with no
             * error. The flag lets them fall back to opening the PDF in a new
             * tab, which is exactly what the inline preview route already
             * serves.
             */
            init() {
                window.crmPdfPreviewMounted = true;
            },

            async open(detail) {
                if (!detail || !detail.url) {
                    return;
                }

                this.destroyDocument();

                const mine = ++token;

                this.isOpen = true;
                this.loading = true;
                this.error = null;
                this.url = detail.url;
                this.downloadUrl = detail.downloadUrl || detail.url;
                this.title = detail.title || '';
                this.pages = 0;
                this.scale = 1.0;
                this.$refs.pages.replaceChildren();

                try {
                    // The dynamic import is what keeps pdf.js out of the main
                    // bundle — the chunk and its worker are fetched here, once,
                    // and cached by the browser for every later preview.
                    viewer = viewer || (await import('./pdf-preview'));

                    controller = new AbortController();

                    const loaded = await viewer.loadDocument(detail.url, controller.signal);

                    if (mine !== token) {
                        viewer.destroyDocument(loaded);

                        return;
                    }

                    doc = loaded;
                    this.ready = true;
                    this.pages = loaded.numPages;

                    await this.renderPages(mine);
                } catch (error) {
                    this.fail(mine, error);
                } finally {
                    if (mine === token) {
                        this.loading = false;
                    }
                }
            },

            /*
             * Surface a failure, unless a newer open/zoom/close has already
             * moved on from the request that produced it.
             *
             * Staleness is the common case rather than the exception here:
             * tearing a document down rejects whatever getPage()/render()
             * calls were still in flight against it, so every close
             * mid-render lands in a catch. Those are expected and must stay
             * silent; a live one must not.
             */
            fail(mine, error) {
                if (mine !== token) {
                    return;
                }

                this.error = error && error.message ? error.message : String(error);
            },

            async renderPages(mine) {
                if (!doc || !viewer) {
                    return;
                }

                await viewer.renderPages(
                    doc,
                    this.$refs.pages,
                    this.scale,
                    () => mine !== token
                );
            },

            async zoom(delta) {
                if (!doc) {
                    return;
                }

                const scale = Math.min(3, Math.max(0.5, Math.round((this.scale + delta) * 100) / 100));

                if (scale === this.scale) {
                    return;
                }

                this.scale = scale;

                const mine = ++token;

                this.loading = true;
                // A previous zoom may have failed; this attempt owns the outcome.
                this.error = null;

                try {
                    await this.renderPages(mine);
                } catch (error) {
                    // Without this the rejection escapes as an unhandled promise
                    // (Alpine does not await @click handlers) and the drawer just
                    // sits there showing the pre-zoom canvases as though nothing
                    // had been asked of it.
                    this.fail(mine, error);
                } finally {
                    if (mine === token) {
                        this.loading = false;
                    }
                }
            },

            zoomIn() {
                return this.zoom(0.25);
            },

            zoomOut() {
                return this.zoom(-0.25);
            },

            get zoomPercent() {
                return Math.round(this.scale * 100);
            },

            destroyDocument() {
                token++;

                if (controller) {
                    controller.abort();
                    controller = null;
                }

                if (viewer) {
                    viewer.destroyDocument(doc);
                }

                doc = null;
                this.ready = false;
            },

            close() {
                if (!this.isOpen) {
                    return;
                }

                this.isOpen = false;
                this.loading = false;
                this.error = null;
                this.pages = 0;

                this.destroyDocument();
                this.$refs.pages.replaceChildren();
            },
        };
    });
});