<?php

/*
 * Drag-and-drop ordering of the product lines on the quote/order/invoice/
 * purchase order forms.
 *
 * The services save each line's `order` from its position in the submitted
 * rows, so the component has to load the rows in that order, move them on a
 * drag, and key them by something that survives the move.
 */

use Illuminate\Http\Request;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\ModelProducts;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Services\OrderService;
use VentureDrake\LaravelCrm\Services\PurchaseOrderService;

/**
 * An order whose lines were saved in a different order from their ids:
 * A is last, B first and C second.
 */
function orderWithReorderedLines(): Order
{
    $order = Order::create(['description' => 'Order', 'currency' => 'USD']);

    foreach (['A' => 3, 'B' => 1, 'C' => 2] as $name => $position) {
        $order->orderProducts()->create([
            'product_id' => Product::create(['name' => $name])->id,
            // price and amount are set in units; tax_amount has no mutator, so cents.
            'quantity' => 1, 'price' => 10, 'amount' => 10,
            'tax_rate' => 10, 'tax_amount' => 100, 'order' => $position,
        ]);
    }

    return $order;
}

function productLineNames(array $products): array
{
    return array_column($products, 'name');
}

test('it lists the lines in their saved order rather than id order', function () {
    $this->actingAsUser();

    $products = Livewire::test(ModelProducts::class, ['model' => orderWithReorderedLines()])->get('products');

    expect(productLineNames($products))->toBe(['B', 'C', 'A']);
});

test('it gives every row a unique key', function () {
    $this->actingAsUser();

    $component = Livewire::test(ModelProducts::class, ['model' => orderWithReorderedLines()])
        ->call('add');

    $keys = array_column($component->get('products'), 'row_key');

    expect($keys)->toHaveCount(4)
        ->and(array_unique($keys))->toHaveCount(4);
});

test('it moves a line and tells the parent form the new order', function () {
    $this->actingAsUser();

    $component = Livewire::test(ModelProducts::class, ['model' => orderWithReorderedLines()]);
    $keys = array_column($component->get('products'), 'row_key');

    $component->call('moveProduct', 0, 2);

    expect(productLineNames($component->get('products')))->toBe(['C', 'A', 'B'])
        // The row carries its key with it, so the view follows it.
        ->and(array_column($component->get('products'), 'row_key'))->toBe([$keys[1], $keys[2], $keys[0]]);

    $component->assertSet('sub_total', 30.0)
        ->assertSet('tax', 3.0)
        ->assertSet('total', 33.0)
        ->assertDispatched('model-products-updated', fn ($event, $params) => productLineNames($params['products']) === ['C', 'A', 'B']);
});

test('it ignores a move outside the rows', function (int $from, int $to) {
    $this->actingAsUser();

    $component = Livewire::test(ModelProducts::class, ['model' => orderWithReorderedLines()]);

    $component->call('moveProduct', $from, $to);

    expect(productLineNames($component->get('products')))->toBe(['B', 'C', 'A']);
})->with([
    'from below zero' => [-1, 1],
    'to below zero' => [0, -1],
    'from past the end' => [3, 0],
    'to past the end' => [0, 3],
]);

test('it shows the drag handle only on editable rows', function () {
    $this->actingAsUser();

    $order = orderWithReorderedLines();

    Livewire::test(ModelProducts::class, ['model' => $order])
        ->assertSeeHtml('model-products-handle');

    Livewire::test(ModelProducts::class, ['model' => $order, 'from' => 'Order', 'creating' => 'Invoice'])
        ->assertDontSeeHtml('model-products-handle');

    $delivery = Delivery::create(['order_id' => $order->id]);
    $orderProduct = $order->orderProducts()->first();
    $delivery->deliveryProducts()->create([
        'product_id' => $orderProduct->product_id, 'order_product_id' => $orderProduct->id, 'quantity' => 1,
    ]);

    Livewire::test(ModelProducts::class, ['model' => $delivery, 'creating' => 'Delivery'])
        ->assertDontSeeHtml('model-products-handle');
});

test('a reordered order saves its lines in the new order', function () {
    $this->actingAsUser();

    $order = orderWithReorderedLines();

    $products = Livewire::test(ModelProducts::class, ['model' => $order, 'currency' => 'USD'])
        ->call('moveProduct', 0, 2)
        ->get('products');

    app(OrderService::class)->update(new Request([
        'description' => 'Order', 'currency' => 'USD', 'sub_total' => 30, 'tax' => 3, 'total' => 33,
        'products' => $products,
    ]), $order);

    $lines = $order->orderProducts()->orderBy('order')->get();

    expect($lines->map->displayName()->all())->toBe(['C', 'A', 'B'])
        ->and($lines->pluck('order')->all())->toBe([1, 2, 3]);

    // And the next edit opens in that order.
    $reopened = Livewire::test(ModelProducts::class, ['model' => $order->fresh()])->get('products');

    expect(productLineNames($reopened))->toBe(['C', 'A', 'B']);
});

test('a new purchase order numbers its lines', function () {
    $this->actingAsUser();

    $purchaseOrder = app(PurchaseOrderService::class)->create(new Request([
        'currency' => 'USD', 'delivery_type' => 'pickup',
        'products' => [
            ['id' => Product::create(['name' => 'A'])->id, 'quantity' => 1, 'unit_price' => 10, 'amount' => 10, 'comments' => null],
            ['id' => Product::create(['name' => 'B'])->id, 'quantity' => 2, 'unit_price' => 4, 'amount' => 8, 'comments' => null],
        ],
    ]));

    expect($purchaseOrder->purchaseOrderLines()->orderBy('id')->pluck('order')->all())->toBe([1, 2]);
});
