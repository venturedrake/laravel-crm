<?php

/*
 * A document's header totals are the sum of the lines it saved.
 *
 * The services stored the header totals the request carried. The line-items
 * form could post a row set to quantity 0 with its old amount still counted
 * in those totals; the service then dropped the row, and the header billed
 * for a line the document did not have (INV-2286: an order line of
 * 7 x $1,499 missing from the lines but still in the header).
 */

use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Services\InvoiceService;
use VentureDrake\LaravelCrm\Services\OrderService;
use VentureDrake\LaravelCrm\Services\PurchaseOrderService;
use VentureDrake\LaravelCrm\Services\QuoteService;

beforeEach(function () {
    $this->actingAsUser();
});

/**
 * Two rows: 2 x $100 taxed at 10%, and a row zeroed in the form that still
 * carries its 7 x $1,499 amount. The posted totals count both.
 */
function inflatedTotalsRequest(array $extra = []): Request
{
    $gst = TaxRate::create(['name' => 'GST', 'rate' => 10, 'default' => false]);
    $kept = Product::create(['name' => 'Kept', 'tax_rate_id' => $gst->id]);
    $zeroed = Product::create(['name' => 'Zeroed', 'tax_rate_id' => $gst->id]);

    return new Request($extra + [
        'title' => 'Totals', 'currency' => 'USD', 'delivery_type' => 'pickup',
        'sub_total' => 10693, 'tax' => 1069.30, 'total' => 11762.30,
        'products' => [
            ['id' => $kept->id, 'quantity' => 2, 'unit_price' => 100, 'amount' => 200, 'comments' => null],
            ['id' => $zeroed->id, 'quantity' => 0, 'unit_price' => 1499, 'amount' => 10493, 'comments' => null],
        ],
    ]);
}

dataset('documents', [
    // [service, model, discount/adjustments apply]
    'quote' => [QuoteService::class, Quote::class, true],
    'order' => [OrderService::class, Order::class, true],
    'invoice' => [InvoiceService::class, Invoice::class, false],
    'purchase order' => [PurchaseOrderService::class, PurchaseOrder::class, false],
]);

test('create stores the header totals of the lines it saved', function (string $service, string $model, bool $adjustable) {
    $document = app($service)->create(inflatedTotalsRequest(['discount' => 10, 'adjustment' => 5]))->fresh();

    expect((int) $document->getRawOriginal('subtotal'))->toBe(20000)
        ->and((int) $document->getRawOriginal('tax'))->toBe(2000)
        // Discount and adjustments are only on quotes and orders.
        ->and((int) $document->getRawOriginal('total'))->toBe($adjustable ? 21500 : 22000);
})->with('documents');

test('update stores the header totals of the lines it saved', function (string $service, string $model) {
    $document = $model::create(['currency' => 'USD'] + ($model === Quote::class ? ['title' => 'Totals'] : []));

    app($service)->update(inflatedTotalsRequest(), $document);

    $document->refresh();

    expect((int) $document->getRawOriginal('subtotal'))->toBe(20000)
        ->and((int) $document->getRawOriginal('tax'))->toBe(2000)
        ->and((int) $document->getRawOriginal('total'))->toBe(22000);
})->with('documents');

test('line tax on the half cent rounds up, as the form shows it', function (string $service) {
    // $0.35 at 10% is exactly 3.5 cents. Taxed in float dollars it came out
    // 0.034999..., which the PO line's mutator stored as 3 while the form
    // showed 4.
    $gst = TaxRate::create(['name' => 'GST', 'rate' => 10, 'default' => false]);
    $product = Product::create(['name' => 'Half cent', 'tax_rate_id' => $gst->id]);

    $document = app($service)->create(new Request([
        'title' => 'Half cent', 'currency' => 'USD', 'delivery_type' => 'pickup',
        'products' => [
            ['id' => $product->id, 'quantity' => 1, 'unit_price' => 0.35, 'amount' => 0.35, 'comments' => null],
        ],
    ]))->fresh();

    expect((int) $document->getRawOriginal('tax'))->toBe(4)
        ->and((int) $document->getRawOriginal('total'))->toBe(39);
})->with([
    'quote' => QuoteService::class,
    'order' => OrderService::class,
    'invoice' => InvoiceService::class,
    'purchase order' => PurchaseOrderService::class,
]);

test('updating an unpaid invoice keeps amount due in step with the derived total', function () {
    // A stale balance from before the header was derived from the lines.
    $invoice = Invoice::create(['currency' => 'USD', 'amount_due' => 1]);

    app(InvoiceService::class)->update(inflatedTotalsRequest(), $invoice);

    expect((int) $invoice->fresh()->getRawOriginal('amount_due'))->toBe(22000);
});
