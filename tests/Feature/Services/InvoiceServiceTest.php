<?php

use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Services\InvoiceService;

beforeEach(function () {
    $this->actingAsUser();
});

test('service creates an invoice with minimum data', function () {
    // The header totals come from the saved lines.
    $gst = TaxRate::create(['name' => 'GST', 'rate' => 10, 'default' => false]);
    $product = Product::create(['name' => 'Taxed', 'tax_rate_id' => $gst->id]);

    $invoice = app(InvoiceService::class)->create(new Request([
        'reference' => 'INV-1', 'currency' => 'USD',
        'issue_date' => '2025-01-01', 'due_date' => '2025-01-31',
        'sub_total' => 100, 'tax' => 10, 'total' => 110,
        'products' => [['id' => $product->id, 'quantity' => 1, 'unit_price' => 100, 'amount' => 100, 'comments' => null]],
    ]));

    expect($invoice)->toBeInstanceOf(Invoice::class);
    expect($invoice->reference)->toBe('INV-1');
    expect((int) $invoice->fresh()->getRawOriginal('total'))->toBe(11000);
});

test('service attaches person org and order', function () {
    $person = Person::create(['first_name' => 'C']);
    $org = Organization::create(['name' => 'Acme']);
    $order = Order::create(['description' => 'Order']);

    $invoice = app(InvoiceService::class)->create(new Request([
        'currency' => 'USD',
        'order_id' => $order->id,
    ]), $person, $org);

    expect($invoice->person_id)->toBe($person->id);
    expect($invoice->organization_id)->toBe($org->id);
    expect($invoice->order_id)->toBe($order->id);
});

test('service updates an invoice', function () {
    $invoice = Invoice::create(['currency' => 'USD']);

    app(InvoiceService::class)->update(new Request([
        'reference' => 'NEW', 'currency' => 'GBP',
        'sub_total' => 1, 'tax' => 0, 'total' => 1,
    ]), $invoice);

    $fresh = $invoice->fresh();
    expect($fresh->reference)->toBe('NEW');
    expect($fresh->currency)->toBe('GBP');
});

test('service keeps the tax on existing lines when the invoice is updated', function () {
    // Created first so it holds the id the existing line will get - the lookup
    // used to find the product by the line's id and land on this one, untaxed.
    Product::create(['name' => 'Decoy']);

    $gst = TaxRate::create(['name' => 'GST', 'rate' => 10, 'default' => false]);
    $product = Product::create(['name' => 'Taxed', 'tax_rate_id' => $gst->id]);

    $invoice = Invoice::create(['currency' => 'USD']);
    $existing = $invoice->invoiceLines()->create([
        'product_id' => $product->id, 'quantity' => 1, 'price' => 1200,
        'amount' => 1200, 'tax_rate' => 10, 'tax_amount' => 120, 'currency' => 'USD',
    ]);

    expect($existing->id)->not->toBe($product->id);

    app(InvoiceService::class)->update(new Request([
        'currency' => 'USD', 'sub_total' => 1800, 'tax' => 180, 'total' => 1980,
        'products' => [
            ['invoice_line_id' => $existing->id, 'id' => $product->id, 'quantity' => 1, 'unit_price' => '1,200', 'amount' => '1,200', 'comments' => null],
            ['id' => $product->id, 'quantity' => 1, 'unit_price' => '600', 'amount' => '600', 'comments' => null],
        ],
    ]), $invoice);

    $lines = $invoice->invoiceLines()->orderBy('order')->get();

    expect($lines)->toHaveCount(2);
    expect((float) $lines[0]->tax_rate)->toBe(10.0);
    expect((int) $lines[0]->getRawOriginal('tax_amount'))->toBe(12000);
    expect((float) $lines[1]->tax_rate)->toBe(10.0);
    expect((int) $lines[1]->getRawOriginal('tax_amount'))->toBe(6000);
});

/*
 * Line amounts are priced from the unit price and quantity. The request's
 * amount is only what the form last showed - a follow-up invoice from an
 * order used to post the order line's full amount against a part quantity.
 */

test('service prices a new line from its unit price and quantity', function () {
    $gst = TaxRate::create(['name' => 'GST', 'rate' => 10, 'default' => false]);
    $product = Product::create(['name' => 'Taxed', 'tax_rate_id' => $gst->id]);

    $invoice = app(InvoiceService::class)->create(new Request([
        'currency' => 'USD',
        'products' => [
            // 3 of 5 left to invoice, still carrying the order line's 5 x 2000.
            ['id' => $product->id, 'quantity' => 3, 'unit_price' => '2,000.00', 'amount' => '10,000.00', 'comments' => null],
            ['id' => $product->id, 'quantity' => 1.25, 'unit_price' => '333.20', 'amount' => '833.00', 'comments' => null],
        ],
    ]));

    $lines = $invoice->invoiceLines()->orderBy('order')->get();

    expect($lines->pluck('amount')->all())->toBe([600000, 41650])
        ->and($lines->pluck('tax_amount')->all())->toBe([60000, 4165]);
});

test('service reprices an existing line and a line added on update', function () {
    $product = Product::create(['name' => 'Plain']);

    $invoice = Invoice::create(['currency' => 'USD']);
    $existing = $invoice->invoiceLines()->create([
        'product_id' => $product->id, 'quantity' => 1, 'price' => 100,
        'amount' => 100, 'tax_rate' => 0, 'tax_amount' => 0, 'currency' => 'USD',
    ]);

    app(InvoiceService::class)->update(new Request([
        'currency' => 'USD',
        'products' => [
            ['invoice_line_id' => $existing->id, 'id' => $product->id, 'quantity' => 2.5, 'unit_price' => '333.33', 'amount' => '999.99', 'comments' => null],
            ['id' => $product->id, 'quantity' => 3.5, 'unit_price' => '9.99', 'amount' => '1.00', 'comments' => null],
        ],
    ]), $invoice);

    expect($invoice->invoiceLines()->orderBy('order')->pluck('amount')->all())->toBe([83333, 3497]);
});
