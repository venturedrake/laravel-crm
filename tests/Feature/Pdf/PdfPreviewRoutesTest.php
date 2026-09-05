<?php

use Barryvdh\DomPDF\ServiceProvider as DomPdfServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\HeaderUtils;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Setting;

/*
 * The inline `{model}/preview` routes that back the pdf.js preview drawer.
 *
 * Sibling of PdfDownloadRoutesTest, and it exists for the same reason: both
 * actions now delegate to a shared buildPdf() on each controller, so the only
 * thing that can drift between them is the response envelope. That envelope is
 * what the drawer depends on — a Content-Disposition of `attachment` would make
 * the browser save the file instead of handing pdf.js the bytes, and the wrong
 * Content-Type would break the fetch on hosts that sniff it.
 *
 * The document *contents* are already covered end-to-end by
 * PdfDownloadRoutesTest across all (docType × template) pairs; duplicating that
 * grid here would only re-test buildPdf(). What is asserted here instead is that
 * each preview route exists, renders through the same pipeline without throwing,
 * and serves inline.
 */

beforeEach(function () {
    // Barryvdh's DomPDF ServiceProvider isn't in the test suite's
    // package-provider list; register it here so `Pdf::loadView(...)`
    // resolves the `dompdf.wrapper` binding. Same discipline as
    // PdfDownloadRoutesTest.
    $this->app->register(DomPdfServiceProvider::class);

    $this->actingAsUser(['crm_access' => 1]);
    Gate::before(fn () => true);

    Setting::query()->delete();
    app('laravel-crm.settings')->forgetCache();
});

dataset('preview_docTypes', ['quote', 'order', 'delivery', 'invoice', 'purchase-order']);

/**
 * Create a saved record of `$docType` and return its preview route name and
 * route parameters.
 *
 * Fixtures mirror PdfDownloadRoutesTest's (issue/due dates where the blades
 * format them unconditionally, a real parent order for deliveries) but are
 * duplicated rather than shared: Pest includes test files one at a time, so a
 * helper defined in a sibling file is not reliably in scope here.
 *
 * @return array{0: string, 1: array<string, mixed>}
 */
function previewRouteFor(string $docType): array
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

            return ['laravel-crm.quotes.preview', ['quote' => $quote]];

        case 'order':
            $order = Order::create($money);

            return ['laravel-crm.orders.preview', ['order' => $order]];

        case 'invoice':
            $invoice = Invoice::create(array_merge($money, [
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
            ]));

            return ['laravel-crm.invoices.preview', ['invoice' => $invoice]];

        case 'purchase-order':
            $purchaseOrder = PurchaseOrder::create(array_merge($money, [
                'issue_date' => now(),
            ]));

            return ['laravel-crm.purchase-orders.preview', ['purchaseOrder' => $purchaseOrder]];

        case 'delivery':
            $order = Order::create($money);
            $delivery = Delivery::create(['order_id' => $order->id]);

            return ['laravel-crm.deliveries.preview', ['delivery' => $delivery]];
    }

    throw new InvalidArgumentException('Unknown doc type: '.$docType);
}

test('every preview route serves a valid PDF inline', function (string $docType) {
    // Deliveries render their line items off `crm_delivery_products`, absent
    // from the core TestSchema. Same gate as PdfDownloadRoutesTest.
    if ($docType === 'delivery' && ! Schema::hasTable('crm_delivery_products')) {
        $this->markTestSkipped('crm_delivery_products table not present in this test schema');
    }

    [$routeName, $parameters] = previewRouteFor($docType);

    $response = $this->get(route($routeName, $parameters));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');

    $disposition = $response->headers->get('Content-Disposition');

    // `inline` is the whole point of the route: `attachment` would make the
    // browser save the file rather than let pdf.js render it in the drawer.
    expect($disposition)->toStartWith('inline;');
    expect($disposition)->toContain('.pdf');

    $body = $response->getContent();

    expect(strlen($body))->toBeGreaterThan(0);
    expect(substr($body, 0, 5))->toBe('%PDF-');
})->with('preview_docTypes');

test('preview and download drive the same blade with the same view data', function (string $docType) {
    // The contract that lets download() and preview() share one buildPdf(): if
    // a future edit gives either action its own loadView() call, the drawer
    // would start showing a different document from the one the user
    // downloads — silently, since both would still return a valid PDF.
    //
    // Asserted on the view name + data rather than on the PDF bytes: DomPDF
    // stamps a fresh trailer /ID and CreationDate into every render, so two
    // renders of the identical document are never byte-equal.
    if ($docType === 'delivery' && ! Schema::hasTable('crm_delivery_products')) {
        $this->markTestSkipped('crm_delivery_products table not present in this test schema');
    }

    [$previewRoute, $parameters] = previewRouteFor($docType);
    $downloadRoute = str_replace('.preview', '.download', $previewRoute);

    [$previewView, $previewData] = capturedPdfView($this, $previewRoute, $parameters);
    [$downloadView, $downloadData] = capturedPdfView($this, $downloadRoute, $parameters);

    expect($previewView)->toBe($downloadView);
    expect($previewData)->toEqual($downloadData);
})->with('preview_docTypes');

test('preview serves inline where its download twin serves an attachment, under the same filename', function (string $docType) {
    if ($docType === 'delivery' && ! Schema::hasTable('crm_delivery_products')) {
        $this->markTestSkipped('crm_delivery_products table not present in this test schema');
    }

    [$previewRoute, $parameters] = previewRouteFor($docType);
    $downloadRoute = str_replace('.preview', '.download', $previewRoute);

    $preview = $this->get(route($previewRoute, $parameters))->headers->get('Content-Disposition');
    $download = $this->get(route($downloadRoute, $parameters))->headers->get('Content-Disposition');

    expect($preview)->toStartWith('inline;');
    expect($download)->toStartWith('attachment;');

    // Both actions read pdfFilename(), so the name the drawer's Download
    // button produces matches the plain download button's to the character.
    expect(substr($preview, strlen('inline;')))->toBe(substr($download, strlen('attachment;')));
})->with('preview_docTypes');

test('a filename that plain interpolation would break still round-trips through the header', function (string $disposition) {
    // The names in these headers are not ours. `quote_id` is prefix.number,
    // and the prefix is whatever an admin typed under Settings; invoice and
    // purchase order names carry a number straight out of Xero. Interpolating
    // one into `filename="..."` lets a quote character close the string early
    // and truncate the download name, and leaves a non-ASCII one with no
    // RFC 6266 `filename*` twin.
    Setting::create(['name' => 'quote_prefix', 'value' => 'Ø"Q/']);
    app('laravel-crm.settings')->forgetCache();

    $quote = Quote::create([
        'title' => 'Awkwardly prefixed quote',
        'subtotal' => 100,
        'tax' => 10,
        'total' => 110,
        'currency' => 'USD',
    ]);

    // Guard the fixture: if the prefix ever stopped reaching quote_id this
    // test would still pass while asserting nothing.
    expect($quote->quote_id)->toContain('"')->toContain('Ø');

    $route = $disposition === 'inline' ? 'laravel-crm.quotes.preview' : 'laravel-crm.quotes.download';

    $response = $this->get(route($route, ['quote' => $quote]));

    // A path separator in the prefix is the case that makes
    // HeaderUtils::makeDisposition() throw, so the route 500ing here is a
    // real failure mode and not just a cosmetic one.
    $response->assertOk();

    $header = $response->headers->get('Content-Disposition');
    $parsed = HeaderUtils::combine(HeaderUtils::split($header, ';='));

    expect($parsed)->toHaveKey($disposition);

    // Parsing the header back has to yield one whole filename, not the
    // fragment an unescaped quote would leave behind.
    expect($parsed['filename'])->toStartWith('quote-')->toEndWith('.pdf');

    // The non-ASCII original survives in the filename* half.
    expect($parsed)->toHaveKey('filename*');
    expect(rawurldecode(substr($parsed['filename*'], strlen("utf-8''"))))
        ->toContain('Ø')
        ->toEndWith('.pdf');
})->with(['attachment', 'inline']);

/**
 * Hit `$routeName` and return the PDF blade the controller drove, as
 * [view name, view data].
 *
 * A view composer captures the pair at render time — the same technique
 * PdfDownloadRoutesTest uses, since DomPDF compresses its content streams and
 * nothing about the view data is greppable in the response body.
 *
 * Models in the captured data are reduced to a class:key string. Two requests
 * hydrate two separate Eloquent instances of the same row, and comparing those
 * directly would drown a real difference in incidental loaded-relation and
 * attribute-casting noise.
 *
 * @param  array<string, mixed>  $parameters
 * @return array{0: string, 1: array<string, mixed>}
 */
function capturedPdfView($test, string $routeName, array $parameters): array
{
    $captured = null;

    View::composer('laravel-crm::pdfs.*', function ($view) use (&$captured) {
        // Only the outermost document blade, not the layout partials it pulls
        // in — the first composed view is the one the controller named.
        $captured ??= [$view->name(), $view->getData()];
    });

    $test->get(route($routeName, $parameters))->assertOk();

    expect($captured)->not->toBeNull();

    [$name, $data] = $captured;

    // Blade internals injected during the render, not caller-supplied data.
    unset($data['__env'], $data['app'], $data['errors']);

    $data = array_map(
        fn ($value) => $value instanceof Model ? get_class($value).':'.$value->getKey() : $value,
        $data
    );

    return [$name, $data];
}

/*
 * The `can:view,<model>` gate on these routes is covered in
 * RouteAuthorizationTest — it cannot live here, because this file's beforeEach
 * installs a permissive `Gate::before` to build fixtures and a Gate::before
 * callback cannot be unregistered for a single test.
 */
