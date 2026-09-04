<?php

use VentureDrake\LaravelCrm\Support\PdfTemplateRegistry;

/*
 * The cheap, broad net: every variable a PDF blade reads must be a key its
 * callers actually pass.
 *
 * This is the test that would have caught the `contactDetails` regression at
 * the commit that introduced it. That commit repointed 14 call sites from
 * `laravel-crm::quotes.pdf` to `PdfTemplateRegistry::viewForModel(...)` —
 * changing the view name and nothing else — while the new themed blades
 * referenced a variable the quote/order/delivery callers had never passed.
 * Nothing compared the two sides, so the suite stayed green and every
 * non-classic download 500'd.
 *
 * Static analysis, no rendering: it walks each blade (following @extends and
 * @include one file at a time), collects the `$var` reads, and diffs them
 * against the literal `'key' =>` entries of the array each call site hands
 * `loadView()`.
 *
 * How it complements the other suites:
 *   - PdfDownloadRoutesTest and PdfSendTest render for real, so they catch
 *     wrong *values* — but only for the fixtures and combinations they
 *     bother to set up.
 *   - This one covers every (call site × slug) combination for free, but
 *     only catches *missing keys*: a key present with a wrong value passes.
 *
 * Caveat: it reads source with regexes, so reformatting a call site's
 * loadView() array — or writing the view-data array as a variable rather
 * than inline — will make it stop seeing that call site. The
 * `every call site is discovered` test below guards exactly that, so a
 * reformat fails loudly instead of silently reducing coverage.
 */

/**
 * Blade compiler internals and loop machinery that appear as `$vars` in a
 * template's source but are never supplied by a caller.
 */
const PDF_BLADE_INTERNALS = [
    '__env', '__currentLoopData', '__empty_1', '__empty_2', '__data',
    'loop', 'slot', 'attributes', 'component', 'errors', 'app', 'this',
    'message', 'componentName', '__laravel_slots',
];

/**
 * Read `$path` and return every variable it reads from the caller's scope.
 *
 * Follows literal `@extends` / `@include` targets in the `laravel-crm::`
 * namespace, because Blade pipes the parent scope into both verbatim — the
 * classic wrappers are nothing but a one-line `@include`, so not following
 * them would leave the entire classic column asserting nothing.
 *
 * Variables the template binds itself — `@foreach (... as $x)` locals and
 * `@php $x = ...` assignments — are subtracted, as are the compiler
 * internals above.
 *
 * @param  array<int, string>  $seen  Guards against an include cycle.
 * @return array<int, string>
 */
function pdfBladeVariables(string $path, array &$seen = []): array
{
    $real = realpath($path);

    if ($real === false || in_array($real, $seen, true)) {
        return [];
    }

    $seen[] = $real;

    $source = file_get_contents($real);

    preg_match_all('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', $source, $matches);
    $vars = array_unique($matches[1]);

    // Locals bound by the template itself: `as $value` and `as $k => $v`.
    $bound = [];
    preg_match_all(
        '/@(?:foreach|forelse)\s*\(.*?\s+as\s+(?:\$([a-zA-Z_][a-zA-Z0-9_]*)\s*=>\s*)?\$([a-zA-Z_][a-zA-Z0-9_]*)/s',
        $source,
        $loopMatches
    );
    $bound = array_merge($bound, array_filter($loopMatches[1]), $loopMatches[2]);

    // `@php $x = ... @endphp` and inline `{{ $x = ... }}` assignments.
    preg_match_all('/\$([a-zA-Z_][a-zA-Z0-9_]*)\s*=[^=]/', $source, $assignMatches);
    $bound = array_merge($bound, $assignMatches[1]);

    $vars = array_diff($vars, $bound, PDF_BLADE_INTERNALS);

    // Follow the parent layout and any literal includes, one hop at a time.
    preg_match_all(
        '/@(?:extends|include)\s*\(\s*[\'"]laravel-crm::([a-zA-Z0-9_.\-]+)[\'"]/',
        $source,
        $includeMatches
    );

    foreach ($includeMatches[1] as $view) {
        $childPath = __DIR__.'/../../../resources/views/'.str_replace('.', '/', $view).'.blade.php';

        $vars = array_merge($vars, pdfBladeVariables($childPath, $seen));
    }

    return array_values(array_unique($vars));
}

/**
 * Blank out comments while preserving line numbering.
 *
 * OrderController carries a commented-out `view(PdfTemplateRegistry::
 * viewForModel('order', ...), [...])` block left over from an earlier
 * revision. Parsing raw source treats it as a 15th live call site and
 * reports a phantom missing key, so comments are tokenised away rather than
 * pattern-matched away — a docblock mentioning `'key' =>` would fool a
 * regex. Newlines are kept so reported line numbers still point at the real
 * source.
 */
function pdfSourceWithoutComments(string $source): string
{
    $out = '';

    foreach (token_get_all($source) as $token) {
        if (is_array($token)) {
            $out .= in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)
                ? str_repeat("\n", substr_count($token[1], "\n"))
                : $token[1];

            continue;
        }

        $out .= $token;
    }

    return $out;
}

/**
 * Extract the literal `'key' =>` entries of the view-data array a call site
 * passes to `loadView()`.
 *
 * Located by finding each `viewForModel('<docType>'` occurrence, then
 * bracket-matching from the `[` that follows the view argument to its
 * partner — more robust than trying to regex a multi-line array whose values
 * contain their own parentheses and `?? null` chains.
 *
 * @return array<int, array{docType: string, keys: array<int, string>, line: int}>
 */
function pdfCallSites(string $path): array
{
    $source = pdfSourceWithoutComments(file_get_contents($path));
    $sites = [];

    preg_match_all(
        '/viewForModel\(\s*[\'"]([a-z\-]+)[\'"]/',
        $source,
        $matches,
        PREG_OFFSET_CAPTURE
    );

    foreach ($matches[0] as $index => [$_, $offset]) {
        $docType = $matches[1][$index][0];

        // The view-data array opens at the first `[` after the view argument.
        $open = strpos($source, '[', $offset);

        if ($open === false) {
            continue;
        }

        $depth = 0;
        $close = null;

        for ($i = $open; $i < strlen($source); $i++) {
            if ($source[$i] === '[') {
                $depth++;
            } elseif ($source[$i] === ']') {
                $depth--;

                if ($depth === 0) {
                    $close = $i;
                    break;
                }
            }
        }

        if ($close === null) {
            continue;
        }

        $array = substr($source, $open, $close - $open + 1);

        preg_match_all('/[\'"]([a-zA-Z_][a-zA-Z0-9_]*)[\'"]\s*=>/', $array, $keyMatches);

        $sites[] = [
            'docType' => $docType,
            'keys' => array_values(array_unique($keyMatches[1])),
            'line' => substr_count(substr($source, 0, $offset), "\n") + 1,
        ];
    }

    return $sites;
}

/**
 * The 14 files that render a PDF blade through `loadView()`.
 *
 * TemplatePreviewController is deliberately absent: it passes a `$data`
 * variable rather than an inline literal, so there is nothing static to
 * read. It is covered end-to-end by TemplatePreviewControllerTest.
 */
function pdfCallSiteFiles(): array
{
    return [
        'src/Http/Controllers/QuoteController.php',
        'src/Http/Controllers/OrderController.php',
        'src/Http/Controllers/DeliveryController.php',
        'src/Http/Controllers/InvoiceController.php',
        'src/Http/Controllers/PurchaseOrderController.php',
        'src/Http/Controllers/Portal/QuoteController.php',
        'src/Http/Controllers/Portal/InvoiceController.php',
        'src/Http/Controllers/Portal/PurchaseOrderController.php',
        'src/Livewire/Quotes/QuoteSend.php',
        'src/Livewire/Invoices/InvoiceSend.php',
        'src/Livewire/PurchaseOrders/PurchaseOrderSend.php',
        'src/Http/Livewire/SendQuote.php',
        'src/Http/Livewire/SendInvoice.php',
        'src/Http/Livewire/SendPurchaseOrder.php',
    ];
}

test('every call site is discovered and yields a non-empty view-data array', function () {
    // The regexes above are the weak point of this suite: if a call site is
    // reformatted past what they match, the contract test below would
    // silently assert nothing. Failing here makes that loud.
    $packageRoot = __DIR__.'/../../../';

    $total = 0;

    foreach (pdfCallSiteFiles() as $file) {
        $sites = pdfCallSites($packageRoot.$file);

        expect($sites)->not->toBeEmpty("no loadView() call site found in {$file}");

        foreach ($sites as $site) {
            expect($site['keys'])->not->toBeEmpty(
                "empty view-data array at {$file}:{$site['line']}"
            );

            expect($site['docType'])->toBeIn(PdfTemplateRegistry::DOC_TYPES);
        }

        $total += count($sites);
    }

    // 14 files, one loadView() each.
    expect($total)->toBe(14);
});

test('every variable a PDF blade reads is a key its call sites pass', function () {
    $packageRoot = __DIR__.'/../../../';
    $slugs = ['modern', 'classic', 'bold', 'compact', 'professional'];

    $failures = [];

    foreach (pdfCallSiteFiles() as $file) {
        foreach (pdfCallSites($packageRoot.$file) as $site) {
            foreach ($slugs as $slug) {
                $view = PdfTemplateRegistry::viewFor($site['docType'], $slug);

                $bladePath = $packageRoot.'resources/views/'
                    .str_replace('.', '/', str_replace('laravel-crm::', '', $view))
                    .'.blade.php';

                $seen = [];
                $required = pdfBladeVariables($bladePath, $seen);

                $missing = array_diff($required, $site['keys']);

                foreach ($missing as $variable) {
                    $failures[] = sprintf(
                        '%s:%d (%s) renders %s which reads $%s',
                        $file,
                        $site['line'],
                        $site['docType'],
                        $slug,
                        $variable
                    );
                }
            }
        }
    }

    expect($failures)->toBe([], "PDF blades read variables their callers never pass:\n".implode("\n", $failures));
});
