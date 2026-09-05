<?php

/*
 * The built front-end half of the PDF preview drawer.
 *
 * There is no JS test runner in this package, and these are all properties
 * that fail silently in exactly one browser or one host configuration — the
 * worst kind to leave uncovered. So they are asserted statically against the
 * source and against the committed build output, the same way
 * PdfViewDataContractTest reads controller source.
 *
 * The build output is committed (public/vendor/laravel-crm/) and published to
 * hosts wholesale, so "what shipped" really is what is on disk here. Assets
 * are content-hashed, hence every lookup goes through the manifest rather
 * than a hardcoded filename.
 */

/**
 * Absolute path to a file in the package.
 */
function packagePath(string $relative): string
{
    return __DIR__.'/../../../'.$relative;
}

/**
 * The built Vite manifest, keyed by source path.
 *
 * @return array<string, array<string, mixed>>
 */
function pdfPreviewManifest(): array
{
    $path = packagePath('public/vendor/laravel-crm/manifest.json');

    expect(is_file($path))->toBeTrue('no built manifest — run `npm run build`');

    return json_decode(file_get_contents($path), true);
}

it('builds the pdf.js chunk from the legacy distribution', function () {
    // The default build calls Promise.withResolvers (Safari 17.4+) on both
    // sides of the worker boundary and polyfills neither. Only `legacy/`
    // carries core-js into both realms — and a main-thread shim cannot reach
    // the worker, which is its own realm with its own globals.
    $source = file_get_contents(packagePath('resources/js/pdf-preview.js'));

    expect($source)->toContain("from 'pdfjs-dist/legacy/build/pdf.mjs'")
        ->and($source)->toContain("from 'pdfjs-dist/legacy/build/pdf.worker.min.mjs?url'");

    // A hand-rolled shim alongside the legacy build would be dead code that
    // reads as though it were the thing providing the support.
    expect($source)->not->toContain('Promise.withResolvers =');
});

it('ships a worker that defines Promise.withResolvers rather than only calling it', function () {
    // The assertion that actually protects older WebKit. `: function` is the
    // core-js descriptor form, i.e. a definition; the default build contains
    // the bare `Promise.withResolvers()` call sites and nothing that assigns.
    $manifest = pdfPreviewManifest();

    $entry = collect($manifest)->first(fn ($entry, $key) => str_contains($key, 'pdf.worker'));

    expect($entry)->not->toBeNull('no pdf.js worker in the built manifest');

    $worker = packagePath('public/vendor/laravel-crm/'.$entry['file']);

    expect(is_file($worker))->toBeTrue("manifest points at a missing worker: {$entry['file']}");
    expect(file_get_contents($worker))->toMatch('/withResolvers\s*:\s*function/');
});

it('points the built chunk at a worker that is actually on disk', function () {
    // The worker URL is baked into the chunk at build time against Vite's
    // base (`/vendor/laravel-crm/`). Getting that wrong 404s the worker and
    // every preview hangs on the first getDocument(), so pin both halves.
    $manifest = pdfPreviewManifest();

    $chunk = packagePath('public/vendor/laravel-crm/'.$manifest['resources/js/pdf-preview.js']['file']);

    expect(is_file($chunk))->toBeTrue('no built pdf-preview chunk');

    preg_match('#"(/vendor/laravel-crm/assets/pdf\.worker[^"]*\.mjs)"#', file_get_contents($chunk), $matches);

    expect($matches)->not->toBeEmpty('the chunk does not request an absolute worker URL under the package base');

    expect(is_file(packagePath('public'.$matches[1])))
        ->toBeTrue("the chunk requests a worker that is not built: {$matches[1]}");
});

it('flags the drawer as mounted so a stale published layout is detectable', function () {
    // layouts/app.blade.php is publishable. A host that published it before
    // this feature shipped has no <x-crm-pdf-preview /> in their copy, so the
    // buttons would dispatch into nothing. The flag is what lets them notice
    // and fall back; see PdfPreviewComponentsTest for the button half.
    expect(file_get_contents(packagePath('resources/js/app.js')))
        ->toContain('window.crmPdfPreviewMounted = true');
});

it('handles a rejection from every render entry point', function () {
    // open() and zoom() both await renderPages(), and Alpine does not await
    // @click handlers — an uncaught rejection in zoom() is an unhandled
    // promise and a drawer that silently keeps showing the pre-zoom canvases.
    // Both paths must route through fail(), which is also what keeps the
    // expected rejections (a close mid-render tears the document down) quiet.
    $source = file_get_contents(packagePath('resources/js/app.js'));

    $awaited = substr_count($source, 'await this.renderPages(');
    $handled = substr_count($source, 'this.fail(');

    expect($awaited)->toBeGreaterThan(0)
        ->and($handled)->toBe($awaited, 'a renderPages() call site is not routed through fail()');
});
