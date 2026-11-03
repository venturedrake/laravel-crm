<?php

/*
 * An order line split across invoices bills exactly the order line.
 *
 * Each invoice rounds its own share to the cent, so ORD-2071's line of
 * 2.5 x 333.33 (833.33, tax 83.33) invoiced as 1.25 + 1.25 came to
 * 416.66 + 416.66 = 833.32, a cent short. The invoice that takes the last
 * of the quantity now bills exactly what is left of the order line.
 */

use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\InvoiceLine;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\OrderProduct;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Services\InvoiceService;

use function VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\lineAmount;

beforeEach(function () {
    $this->actingAsUser();

    $gst = TaxRate::create(['name' => 'GST', 'rate' => 10, 'default' => false]);
    $this->product = Product::create(['name' => 'Panel', 'tax_rate_id' => $gst->id]);

    $this->order = Order::create(['currency' => 'USD']);
    $this->orderProduct = $this->order->orderProducts()->create([
        'product_id' => $this->product->id,
        'quantity' => 2.5,
        'price' => 333.33,
        'amount' => 833.33,
        'tax_rate' => 10,
        'tax_amount' => 8333,
        'currency' => 'USD',
    ]);
});

/**
 * Invoice $quantity of the order line through the service, as the form does.
 */
function invoiceOrderLine($test, $quantity, $unitPrice = 333.33): Invoice
{
    return app(InvoiceService::class)->create(new Request([
        'order_id' => $test->order->id,
        'currency' => 'USD',
        'products' => [[
            'order_product_id' => $test->orderProduct->id,
            'id' => $test->product->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'amount' => 0,
            'comments' => null,
        ]],
    ]));
}

function lineCents(Invoice $invoice): array
{
    $line = $invoice->invoiceLines()->first();

    return [(int) $line->getRawOriginal('amount'), (int) $line->getRawOriginal('tax_amount')];
}

test('an order line split in two invoices adds up to the order line', function () {
    $first = invoiceOrderLine($this, 1.25);
    $second = invoiceOrderLine($this, 1.25);

    expect(lineCents($first))->toBe([41666, 4167])
        ->and(lineCents($second))->toBe([41667, 4166])
        ->and((int) $second->fresh()->getRawOriginal('subtotal'))->toBe(41667)
        ->and((int) $second->fresh()->getRawOriginal('total'))->toBe(45833);
});

test('an order line split in three invoices adds up to the order line', function () {
    $invoices = [invoiceOrderLine($this, 1), invoiceOrderLine($this, 1), invoiceOrderLine($this, 0.5)];

    $lines = array_map(fn ($invoice) => lineCents($invoice), $invoices);

    expect(array_sum(array_column($lines, 0)))->toBe(83333)
        ->and(array_sum(array_column($lines, 1)))->toBe(8333)
        ->and($lines[2])->toBe([16667, 1667]);
});

test('the whole order line on one invoice is the order line', function () {
    expect(lineCents(invoiceOrderLine($this, 2.5)))->toBe([83333, 8333]);
});

test('re-saving the first invoice keeps both invoices adding up to the order line', function () {
    $first = invoiceOrderLine($this, 1.25);
    $second = invoiceOrderLine($this, 1.25);

    $line = $first->invoiceLines()->first();

    app(InvoiceService::class)->update(new Request([
        'currency' => 'USD',
        'products' => [[
            'invoice_line_id' => $line->id,
            'id' => $this->product->id,
            'quantity' => 1.25,
            'unit_price' => 333.33,
            'amount' => 0,
            'comments' => null,
        ]],
    ]), $first->fresh());

    expect(lineCents($first))->toBe([41666, 4167])
        ->and(lineCents($second))->toBe([41667, 4166]);
});

test('re-saving the final invoice keeps billing the remainder', function () {
    invoiceOrderLine($this, 1.25);
    $second = invoiceOrderLine($this, 1.25);

    $line = $second->invoiceLines()->first();

    app(InvoiceService::class)->update(new Request([
        'currency' => 'USD',
        'products' => [[
            // The service prices against the order line the invoice line
            // was saved with - this is ignored.
            'order_product_id' => 999,
            'invoice_line_id' => $line->id,
            'id' => $this->product->id,
            'quantity' => 1.25,
            'unit_price' => 333.33,
            'amount' => 0,
            'comments' => null,
        ]],
    ]), $second->fresh());

    expect(lineCents($second))->toBe([41667, 4166]);
});

test('a line repriced on its invoice is priced on its own', function () {
    invoiceOrderLine($this, 1.25);
    $second = invoiceOrderLine($this, 1.25, 300);

    expect(lineCents($second))->toBe([37500, 3750]);
});

test('the final invoice is priced on its own when an earlier one was repriced', function () {
    invoiceOrderLine($this, 1.25, 0);
    $second = invoiceOrderLine($this, 1.25);

    // Not the 833.33 left of the order line's amount.
    expect(lineCents($second))->toBe([41666, 4167]);
});

test('a line on a soft-deleted invoice is not counted', function () {
    invoiceOrderLine($this, 1.25)->delete();

    $remainder = $this->orderProduct->fresh()->invoiceRemainder();

    expect($remainder)->toBe(['quantity' => 2.5, 'amount' => 83333, 'tax' => 8333]);
});

test('an order line of another order is not priced against', function () {
    invoiceOrderLine($this, 1.25);

    $other = Order::create(['currency' => 'USD']);

    // The last 1.25 of this order line, but on an invoice for another order.
    $invoice = app(InvoiceService::class)->create(new Request([
        'order_id' => $other->id,
        'currency' => 'USD',
        'products' => [[
            'order_product_id' => $this->orderProduct->id,
            'id' => $this->product->id,
            'quantity' => 1.25,
            'unit_price' => 333.33,
            'amount' => 0,
            'comments' => null,
        ]],
    ]));

    expect(lineCents($invoice))->toBe([41666, 4167]);
});

test('the amount check accepts the final invoice line', function () {
    invoiceOrderLine($this, 1.25);
    $second = invoiceOrderLine($this, 1.25);

    $line = InvoiceLine::where('invoice_id', $second->id)->first();

    expect(lineAmount($line))->toBeTrue();

    // And still flags a line that is genuinely wrong.
    $line->update(['amount' => 400]);

    expect(lineAmount($line->fresh()))->toBeFalse();
});

test('the order line has its invoice lines', function () {
    $invoice = invoiceOrderLine($this, 1.25);

    expect($this->orderProduct->invoiceLines()->pluck('invoice_id')->all())->toBe([$invoice->id])
        ->and($invoice->invoiceLines()->first()->orderProduct->is($this->orderProduct))->toBeTrue()
        ->and(OrderProduct::find($this->orderProduct->id)->invoiceRemainder())->toBe(['quantity' => 1.25, 'amount' => 41667, 'tax' => 4166]);
});
