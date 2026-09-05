<?php

use Illuminate\Support\Facades\Blade;

/*
 * The two Blade components behind the PDF preview drawer.
 *
 * They are wired together by a string — the `crm-pdf-preview` window event —
 * rather than by anything the compiler checks, so a rename on one side would
 * leave both halves rendering happily and the button silently dead. These
 * tests pin the event name, the payload shape and the single-instance rule.
 */

it('renders the preview button as a stateless dispatch of the crm-pdf-preview event', function () {
    $html = Blade::render(
        '<x-crm-pdf-preview-button :url="$url" :download-url="$downloadUrl" :title="$title" />',
        [
            'url' => 'https://crm.test/crm/invoices/877/preview',
            'downloadUrl' => 'https://crm.test/crm/invoices/877/download',
            'title' => 'INV-1876',
        ]
    );

    // The event name the drawer listens for. A rename on either side leaves
    // both halves rendering fine and the button silently dead.
    expect($html)->toContain("\$dispatch('crm-pdf-preview'");

    // x-data is what makes $dispatch available; without it the click handler
    // throws and nothing opens.
    expect($html)->toContain('x-data');

    // Js::from escapes every `/` for the JS string literal, so the URLs are
    // only recoverable once the backslashes are dropped.
    $unescaped = str_replace('\\', '', $html);

    expect($unescaped)->toContain('https://crm.test/crm/invoices/877/preview')
        ->and($unescaped)->toContain('https://crm.test/crm/invoices/877/download')
        ->and($unescaped)->toContain('INV-1876');
});

it('escapes a title that would otherwise break out of the Alpine expression', function () {
    // The payload is interpolated into an x-on:click attribute. An apostrophe
    // in a customer name is the ordinary case that a naive '{{ $title }}'
    // would turn into a syntax error, killing the button for that row only.
    $html = Blade::render(
        '<x-crm-pdf-preview-button :url="$url" :download-url="$url" :title="$title" />',
        [
            'url' => 'https://crm.test/crm/quotes/1/preview',
            'title' => "O'Brien & Sons \"Ltd\"",
        ]
    );

    // Js::from wraps the payload in JSON.parse('...') with every quote and
    // ampersand hex-escaped, so none of those characters reach the attribute
    // raw and none of them can terminate the expression early.
    expect($html)->toContain('JSON.parse');

    preg_match("/\\\$dispatch\('crm-pdf-preview', JSON\.parse\('(.*?)'\)\)/", $html, $matches);

    expect($matches)->not->toBeEmpty('the dispatch payload did not render as a JSON.parse literal');

    expect($matches[1])->not->toContain("'")
        ->and($matches[1])->not->toContain('"')
        ->and($matches[1])->not->toContain('&');
});

it('falls back to a new tab when no drawer is mounted', function () {
    // layouts/app.blade.php is publishable, so a host that published it
    // before this feature shipped renders these buttons with nothing
    // listening — the event would go nowhere and the button would be dead
    // with no error anywhere. The drawer sets window.crmPdfPreviewMounted on
    // init; the button branches on it. The preview route already serves the
    // PDF inline, so the browser's own viewer handles the fallback.
    $html = Blade::render(
        '<x-crm-pdf-preview-button :url="$url" :download-url="$downloadUrl" title="INV-1876" />',
        [
            'url' => 'https://crm.test/crm/invoices/877/preview',
            'downloadUrl' => 'https://crm.test/crm/invoices/877/download',
        ]
    );

    expect($html)->toContain('window.crmPdfPreviewMounted ? $dispatch(');

    // The fallback opens the preview URL, not the download one: the point is
    // to still show the document, not to start a download the user did not
    // ask for.
    preg_match("/window\.open\('([^']*)'/", $html, $matches);

    expect($matches)->not->toBeEmpty('no window.open fallback on the button');
    expect(str_replace('\\', '', $matches[1]))->toBe('https://crm.test/crm/invoices/877/preview');

    // Opening a tab with a live window.opener handle is what noopener closes.
    expect($html)->toContain("'noopener'");
});

it('renders the drawer listening on the window for the event the button dispatches', function () {
    $html = Blade::render('<x-crm-pdf-preview />');

    expect($html)->toContain('x-data="crmPdfPreview"')
        ->and($html)->toContain('crm-pdf-preview.window')
        ->and($html)->toContain('keydown.escape.window')
        // The canvas target pdf.js writes into.
        ->and($html)->toContain('x-ref="pages"');
});

it('never binds markup to the raw pdf.js document', function () {
    // pdf.js brand-checks #private fields against the real instance, and
    // everything Alpine holds in its data object is a reactive Proxy. So the
    // pdf.js document has to stay in the component's closure, and any markup
    // that reads `doc` would both fail to react and re-expose the object that
    // must never be proxied.
    //
    // The bug this pins was near-invisible: `numPages` reads a plain property
    // and survives the proxy, so the drawer showed its title and "1 page" and
    // only died on the first getPage() with "Cannot read from private field".
    // `ready` is the reactive stand-in the toolbar binds instead.
    $drawer = file_get_contents(__DIR__.'/../../resources/views/components/pdf-preview.blade.php');

    expect($drawer)->not->toMatch('/x-(?:bind:|show|text|if)[^"]*"[^"]*\bdoc\b/')
        ->and($drawer)->toContain('ready');
});

it('mounts exactly one drawer, in the layout rather than in any Livewire view', function () {
    // A per-row instance inside an index table would give every row its own
    // listener, so one click would open N overlapping drawers. The single
    // mount also has to sit outside every Livewire root, or a wire:navigate
    // visit would tear an open drawer down.
    $package = __DIR__.'/../../';

    $layout = file_get_contents($package.'resources/views/layouts/app.blade.php');

    expect(substr_count($layout, '<x-crm-pdf-preview '))->toBe(1);

    $strays = [];

    $views = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($package.'resources/views/livewire', FilesystemIterator::SKIP_DOTS)
    );

    foreach ($views as $view) {
        if ($view->getExtension() !== 'php') {
            continue;
        }

        if (str_contains(file_get_contents($view->getPathname()), '<x-crm-pdf-preview ')) {
            $strays[] = $view->getFilename();
        }
    }

    expect($strays)->toBe([]);
});
