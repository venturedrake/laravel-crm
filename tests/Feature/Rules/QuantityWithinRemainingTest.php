<?php

/*
 * The rule behind the Order-to-Invoice and Order-to-Delivery quantity cap.
 *
 * It deliberately ignores the row's `quantity_max` - that is a public Livewire
 * property, so it round-trips through the browser - and recomputes what is
 * left from the order line and the records already drawn against it.
 */

use Illuminate\Support\Facades\Validator;
use VentureDrake\LaravelCrm\Http\Rules\QuantityWithinRemaining;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\InvoiceLine;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Product;

function fails(array $products, ?array $drawdown = null): bool
{
    $rule = new QuantityWithinRemaining(
        $products,
        $drawdown['model'] ?? null,
        $drawdown['relation'] ?? null,
        $drawdown['key'] ?? null,
        $drawdown['owner'] ?? null,
    );

    return Validator::make(
        ['products' => $products],
        ['products.*.quantity' => [$rule]]
    )->fails();
}

function invoiceDrawdown(): array
{
    return ['model' => InvoiceLine::class, 'relation' => 'invoice', 'key' => 'invoice_line_id'];
}

function orderLineOf(float $quantity): object
{
    $product = Product::create(['name' => 'By weight']);

    $order = Order::create(['currency' => 'USD']);

    return $order->orderProducts()->create([
        'product_id' => $product->id, 'quantity' => $quantity, 'price' => 1000,
    ]);
}

test('a row with no drawdown model is not capped', function () {
    $orderProduct = orderLineOf(3.5);

    expect(fails([['order_product_id' => $orderProduct->id, 'quantity' => 9999]]))->toBeFalse();
});

test('a row drawing down nothing is not capped', function () {
    expect(fails([['quantity' => 9999]], invoiceDrawdown()))->toBeFalse();
});

test('a row within the order line passes', function () {
    $orderProduct = orderLineOf(3.5);

    expect(fails([['order_product_id' => $orderProduct->id, 'quantity' => 3.5]], invoiceDrawdown()))->toBeFalse();
});

test('a row beyond the order line fails', function () {
    $orderProduct = orderLineOf(3.5);

    expect(fails([['order_product_id' => $orderProduct->id, 'quantity' => 3.6]], invoiceDrawdown()))->toBeTrue();
});

test('an invoice already raised comes off the remainder', function () {
    $orderProduct = orderLineOf(3.5);

    $invoice = Invoice::create(['order_id' => $orderProduct->order_id]);
    $invoice->invoiceLines()->create(['order_product_id' => $orderProduct->id, 'quantity' => 2]);

    expect(fails([['order_product_id' => $orderProduct->id, 'quantity' => 1.5]], invoiceDrawdown()))->toBeFalse()
        ->and(fails([['order_product_id' => $orderProduct->id, 'quantity' => 1.6]], invoiceDrawdown()))->toBeTrue();
});

test('a row does not count its own line against itself', function () {
    $orderProduct = orderLineOf(3.5);

    $invoice = Invoice::create(['order_id' => $orderProduct->order_id]);
    $line = $invoice->invoiceLines()->create(['order_product_id' => $orderProduct->id, 'quantity' => 2]);

    expect(fails([[
        'order_product_id' => $orderProduct->id,
        'invoice_line_id' => $line->id,
        'quantity' => 3.5,
    ]], invoiceDrawdown()))->toBeFalse();
});

test('an existing line left at or below its saved quantity is not capped on edit', function () {
    $orderProduct = orderLineOf(3.5);

    $invoice = Invoice::create(['order_id' => $orderProduct->order_id]);
    $line = $invoice->invoiceLines()->create(['order_product_id' => $orderProduct->id, 'quantity' => 3]);

    $orderProduct->update(['quantity' => 2]);

    $row = fn ($quantity) => [[
        'order_product_id' => $orderProduct->id,
        'invoice_line_id' => $line->id,
        'quantity' => $quantity,
    ]];
    $owned = invoiceDrawdown() + ['owner' => ['invoice_id' => $invoice->id]];

    expect(fails($row(3), $owned))->toBeFalse()
        ->and(fails($row(2.5), $owned))->toBeFalse()
        // Raised past what was saved: capped again.
        ->and(fails($row(3.5), $owned))->toBeTrue()
        // Not this document's line, or no document at all: no exemption.
        ->and(fails($row(3), invoiceDrawdown() + ['owner' => ['invoice_id' => $invoice->id + 1]]))->toBeTrue()
        ->and(fails($row(3), invoiceDrawdown()))->toBeTrue();
});

test('the remainder survives the float dust of a split drawdown', function () {
    $orderProduct = orderLineOf(1.1);

    $invoice = Invoice::create(['order_id' => $orderProduct->order_id]);
    $invoice->invoiceLines()->create(['order_product_id' => $orderProduct->id, 'quantity' => 0.7]);
    $invoice->invoiceLines()->create(['order_product_id' => $orderProduct->id, 'quantity' => 0.4]);

    // 1.1 less 0.7 less 0.4 leaves 1.11e-16, not 0 - anything positive would
    // read as a sliver still available and let the next line through.
    expect(fails([['order_product_id' => $orderProduct->id, 'quantity' => 0.001]], invoiceDrawdown()))->toBeTrue();
});

test('the row index picks the right line', function () {
    $first = orderLineOf(1);
    $second = orderLineOf(10);

    $products = [
        ['order_product_id' => $first->id, 'quantity' => 1],
        ['order_product_id' => $second->id, 'quantity' => 5],
    ];

    expect(fails($products, invoiceDrawdown()))->toBeFalse();

    $products[0]['quantity'] = 2;

    expect(fails($products, invoiceDrawdown()))->toBeTrue();
});
