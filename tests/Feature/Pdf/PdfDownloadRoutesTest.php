<?php

use Barryvdh\DomPDF\ServiceProvider as DomPdfServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Support\PdfContactDetails;

/*
 * The authenticated download routes, rendered through the real controllers.
 *
 * This is the net that was missing when `Undefined variable $contactDetails`
 * shipped. PdfTemplateRenderingTest exercises the same blades, but only ever
 * through the preview controller — which builds its own view-data array and
 * always supplied `contactDetails`. So a themed blade could reference a
 * variable that no download controller passed and the suite stayed green
 * while every real download 500'd.
 *
 * Here the request goes through the actual route → controller → blade path,
 * so the view-data array under test is the one production uses. Every doc
 * type is pinned to each of the 5 template slugs via the record's own
 * `pdf_template` column, which `PdfTemplateRegistry::explicitSlugFor()` reads
 * ahead of settings — no Setting juggling needed to cover the grid.
 */

beforeEach(function () {
    // Barryvdh's DomPDF ServiceProvider isn't in the test suite's
    // package-provider list; register it here so `Pdf::loadView(...)`
    // resolves the `dompdf.wrapper` binding. Same discipline as
    // PdfTemplateRenderingTest.
    $this->app->register(DomPdfServiceProvider::class);

    $this->actingAsUser(['crm_access' => 1]);
    Gate::before(fn () => true);

    Setting::query()->delete();
    app('laravel-crm.settings')->forgetCache();
});

/**
 * Dataset: every (docType, slug) pair reachable from a download route.
 *
 * Slugs are hardcoded rather than read from PdfTemplateRegistry::all(),
 * which calls __() for label resolution — the translator isn't wired up at
 * dataset-discovery time. Mirrors PdfTemplateRenderingTest's reasoning.
 */
dataset('download_docType_pairs', function () {
    $slugs = ['modern', 'classic', 'bold', 'compact', 'professional'];
    $docTypes = ['quote', 'order', 'delivery', 'invoice', 'purchase-order'];

    $rows = [];
    foreach ($docTypes as $docType) {
        foreach ($slugs as $slug) {
            $rows[$docType.'-'.$slug] = [$docType, $slug];
        }
    }

    return $rows;
});

/**
 * Create a saved record of `$docType` pinned to `$slug`, and return it with
 * the route name and route-parameter key its download route expects.
 *
 * Records are built with plain `create()` (no factories in this package) and
 * carry no products — an empty line-item table renders fine and keeps the
 * fixture to the columns the blades actually read.
 *
 * @return array{0: string, 1: array<string, mixed>}
 */
function downloadRouteFor(string $docType, string $slug): array
{
    $money = [
        'subtotal' => 100,
        'tax' => 10,
        'total' => 110,
        'currency' => 'USD',
    ];

    switch ($docType) {
        case 'quote':
            $quote = Quote::create(array_merge($money, ['title' => 'Sample quote']));
            $quote->update(['pdf_template' => $slug]);

            return ['laravel-crm.quotes.download', ['quote' => $quote]];

        case 'order':
            $order = Order::create($money);
            $order->update(['pdf_template' => $slug]);

            return ['laravel-crm.orders.download', ['order' => $order]];

        case 'invoice':
            // Like the purchase-order blades, the invoice blades format
            // `issue_date` and `due_date` unconditionally.
            $invoice = Invoice::create(array_merge($money, [
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
            ]));
            $invoice->update(['pdf_template' => $slug]);

            return ['laravel-crm.invoices.download', ['invoice' => $invoice]];

        case 'purchase-order':
            // The purchase-order blades format `issue_date` unconditionally,
            // so it is a hard requirement of the fixture rather than an
            // optional field.
            $purchaseOrder = PurchaseOrder::create(array_merge($money, [
                'issue_date' => now(),
            ]));
            $purchaseOrder->update(['pdf_template' => $slug]);

            return ['laravel-crm.purchase-orders.download', ['purchaseOrder' => $purchaseOrder]];

        case 'delivery':
            // The delivery blades read the parent order's reference,
            // description and terms, so a delivery without an order renders
            // a half-document at best — give it a real parent.
            $order = Order::create($money);
            $delivery = Delivery::create(['order_id' => $order->id]);
            $delivery->update(['pdf_template' => $slug]);

            return ['laravel-crm.deliveries.download', ['delivery' => $delivery]];
    }

    throw new InvalidArgumentException('Unknown doc type: '.$docType);
}

test('every (docType × template) download route returns a valid non-empty PDF', function (string $docType, string $slug) {
    // Deliveries render their line items off `crm_delivery_products`, absent
    // from the core TestSchema. Same gate as PdfTemplateRenderingTest so
    // production hosts cover all 25 pairs and test environments cover 20.
    if ($docType === 'delivery' && ! Schema::hasTable('crm_delivery_products')) {
        $this->markTestSkipped('crm_delivery_products table not present in this test schema');
    }

    [$routeName, $parameters] = downloadRouteFor($docType, $slug);

    $response = $this->get(route($routeName, $parameters));

    $response->assertOk();

    $body = $response->getContent();

    expect(strlen($body))->toBeGreaterThan(0);
    expect(substr($body, 0, 5))->toBe('%PDF-');
})->with('download_docType_pairs');

test('the shared pdf_contact_details setting reaches every doc type\'s rendered From block', function () {
    // Proves the resolver is actually wired into the download controllers,
    // not merely that they stopped throwing. Asserting on rendered output
    // is what separates "the variable exists" from "the variable carries
    // the configured value" — the blade guard alone would satisfy the
    // former while printing nothing.
    $marker = 'ZZContactBlockMarkerZZ';

    app('laravel-crm.settings')->set(PdfContactDetails::SHARED_KEY, $marker);
    app('laravel-crm.settings')->forgetCache();

    $docTypes = ['quote', 'order', 'invoice'];

    if (Schema::hasTable('crm_delivery_products')) {
        $docTypes[] = 'delivery';
    }

    foreach ($docTypes as $docType) {
        // `modern` is the default template and the one the reported 500
        // came from; the other themed slugs share the identical From block.
        [$routeName, $parameters] = downloadRouteFor($docType, 'modern');

        expect(renderedPdfHtml($this, $routeName, $parameters))->toContain($marker);
    }
});

test('a per-doc-type override still wins over the shared value on a real download', function () {
    // The invoice-specific key is the one existing hosts already have
    // filled. It must keep winning, or upgrading silently rewrites the
    // From block on every invoice.
    app('laravel-crm.settings')->set(PdfContactDetails::SHARED_KEY, 'ZZSharedZZ');
    app('laravel-crm.settings')->set('invoice_contact_details', 'ZZInvoiceOnlyZZ');
    app('laravel-crm.settings')->forgetCache();

    [$routeName, $parameters] = downloadRouteFor('invoice', 'modern');

    $html = renderedPdfHtml($this, $routeName, $parameters);

    expect($html)->toContain('ZZInvoiceOnlyZZ');
    expect($html)->not->toContain('ZZSharedZZ');
});

test('a doc type with no override of its own still gets the shared value', function () {
    // Same settings state as the override test, read from the other side:
    // quotes have no `quote_contact_details` field anywhere, so the shared
    // key is the only thing that can fill their From block.
    app('laravel-crm.settings')->set(PdfContactDetails::SHARED_KEY, 'ZZSharedZZ');
    app('laravel-crm.settings')->set('invoice_contact_details', 'ZZInvoiceOnlyZZ');
    app('laravel-crm.settings')->forgetCache();

    [$routeName, $parameters] = downloadRouteFor('quote', 'modern');

    $html = renderedPdfHtml($this, $routeName, $parameters);

    expect($html)->toContain('ZZSharedZZ');
    expect($html)->not->toContain('ZZInvoiceOnlyZZ');
});

test('the rendered contact block matches the documented (docType x template) matrix', function () {
    // Two combinations deliberately render no contact block, and both are
    // load-bearing for what the settings hint and CHANGELOG promise:
    //
    //   - purchase orders on every template — their layouts pair Supplier
    //     with Delivery details rather than From/To
    //   - every doc type but invoice on `classic` — the pre-2.4.0 layout,
    //     reproduced unchanged, where only the invoice blade had a From block
    //
    // Pinning it here means adding the block to those blades fails loudly
    // rather than silently outdating the copy that describes the gap — and
    // catches the reverse too, a themed blade quietly losing its block.
    $marker = 'ZZMatrixMarkerZZ';

    app('laravel-crm.settings')->set(PdfContactDetails::SHARED_KEY, $marker);
    app('laravel-crm.settings')->forgetCache();

    // [docType, slug] => does the rendered blade print the block?
    $matrix = [
        ['quote', 'classic', false],
        ['order', 'classic', false],
        ['delivery', 'classic', false],
        ['invoice', 'classic', true],
        ['purchase-order', 'classic', false],
        ['quote', 'modern', true],
        ['order', 'bold', true],
        ['delivery', 'compact', true],
        ['invoice', 'professional', true],
        ['purchase-order', 'modern', false],
        ['purchase-order', 'professional', false],
    ];

    // Collected rather than asserted inline so one drifted combination
    // reports itself by name instead of failing on an opaque HTML blob.
    $failures = [];

    foreach ($matrix as [$docType, $slug, $rendersBlock]) {
        if ($docType === 'delivery' && ! Schema::hasTable('crm_delivery_products')) {
            continue;
        }

        [$routeName, $parameters] = downloadRouteFor($docType, $slug);

        $printed = str_contains(renderedPdfHtml($this, $routeName, $parameters), $marker);

        if ($printed !== $rendersBlock) {
            $failures[] = sprintf(
                '%s on %s: expected the contact block to %s, but it %s',
                $docType,
                $slug,
                $rendersBlock ? 'print' : 'be absent',
                $printed ? 'printed' : 'did not'
            );
        }
    }

    expect($failures)->toBe([], "Contact-block matrix drifted:\n".implode("\n", $failures));
});

/**
 * Hit `$routeName` and return the rendered HTML of the PDF blade the
 * controller drove, rather than the PDF bytes.
 *
 * DomPDF compresses its content streams, so a settings value that reached
 * the page is not greppable in the response body. A view composer captures
 * the blade's name and the exact data array the controller passed it; that
 * pair is then re-rendered here to produce the HTML DomPDF was handed.
 * Re-rendering (rather than asserting on the captured array) is deliberate —
 * it proves the value actually prints in the From block, which a bare
 * array-key assertion would not, since `@if($contactDetails ?? null)` could
 * still swallow it.
 *
 * @param  array<string, mixed>  $parameters
 */
function renderedPdfHtml($test, string $routeName, array $parameters): string
{
    $captured = null;

    View::composer('laravel-crm::pdfs.*', function ($view) use (&$captured) {
        // Only the outermost document blade, not the layout partials it
        // pulls in — the first composed view is the one the controller named.
        $captured ??= [$view->name(), $view->getData()];
    });

    $test->get(route($routeName, $parameters))->assertOk();

    expect($captured)->not->toBeNull();

    [$name, $data] = $captured;

    // `__env` and friends are Blade internals injected during the original
    // render; re-passing them confuses the fresh render.
    unset($data['__env'], $data['app'], $data['errors']);

    return View::make($name, $data)->render();
}
