<?php

/*
 * The delivery form has never run validate() at all - the quantity was picked
 * from a `<select>` built by an integer loop over the order's outstanding
 * amount, so an over-delivery was simply unreachable from the browser.
 *
 * Decimal quantities cannot come from a dropdown, so the cap now has to be
 * enforced on submit.
 */

use Illuminate\Support\Str;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Deliveries\DeliveryCreate;
use VentureDrake\LaravelCrm\Livewire\Deliveries\DeliveryEdit;
use VentureDrake\LaravelCrm\Livewire\ModelProducts;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\DeliveryProduct;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\OrderProduct;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\Setting;

/*
 * The delivery blade reaches for tables the minimal TestSchema does not ship,
 * so the component is mounted through a render stub - the same discipline as
 * the Authorization and PdfTemplateSelect suites. mount() and save() still
 * run for real.
 */
class QtyDeliveryCreate extends DeliveryCreate
{
    public function render()
    {
        return '<div></div>';
    }
}

beforeEach(function () {
    $this->actingAsUserWithPermissions([
        'view crm deliveries', 'create crm deliveries', 'edit crm deliveries',
    ]);

    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);
    Setting::updateOrCreate(['name' => 'team'], ['value' => 'related']);

    // HasDeliveryCommon::mountCommon() looks its pipeline up by Invoice.
    $pipeline = Pipeline::create([
        'external_id' => Str::uuid()->toString(),
        'name' => 'Invoice Pipeline',
        'model' => Invoice::class,
    ]);

    $pipeline->pipelineStages()->create([
        'external_id' => Str::uuid()->toString(),
        'name' => 'Draft',
        'order' => 0,
    ]);

    app('laravel-crm.settings')->forgetCache();
});

function orderReadyToDeliver(): array
{
    $product = Product::create(['name' => 'By weight']);

    $order = Order::create(['currency' => 'USD']);
    $orderProduct = $order->orderProducts()->create([
        'product_id' => $product->id, 'quantity' => 3.5, 'price' => 1000, 'amount' => 35000,
    ]);

    return [$order, $orderProduct];
}

test('a delivery cannot exceed the order outstanding quantity', function () {
    [$order, $orderProduct] = orderReadyToDeliver();

    $component = Livewire::test(QtyDeliveryCreate::class, [
        'fromModelType' => 'order', 'fromModelId' => $order->id,
    ]);

    $component->set('products', [[
        'order_product_id' => $orderProduct->id,
        'quantity' => 5,
        'quantity_max' => 3.5,
    ]]);

    $component->call('save')->assertHasErrors('products.0.quantity');

    expect(Delivery::count())->toBe(0)
        ->and(DeliveryProduct::count())->toBe(0);
});

test('a delivery within the order outstanding quantity is accepted', function () {
    [$order, $orderProduct] = orderReadyToDeliver();

    $component = Livewire::test(QtyDeliveryCreate::class, [
        'fromModelType' => 'order', 'fromModelId' => $order->id,
    ]);

    $component->set('products', [[
        'order_product_id' => $orderProduct->id,
        'quantity' => 2.5,
        'quantity_max' => 3.5,
    ]]);

    $component->call('save')->assertHasNoErrors();

    expect(DeliveryProduct::first()->quantity)->toBe(2.5);
});

test('a delivery quantity finer than 3 decimal places is rejected', function () {
    [$order, $orderProduct] = orderReadyToDeliver();

    $component = Livewire::test(QtyDeliveryCreate::class, [
        'fromModelType' => 'order', 'fromModelId' => $order->id,
    ]);

    $component->set('products', [[
        'order_product_id' => $orderProduct->id,
        'quantity' => 1.2345,
        'quantity_max' => 3.5,
    ]]);

    $component->call('save')->assertHasErrors('products.0.quantity');
});

/*
 * `quantity_max` is a public Livewire property, so it round-trips through the
 * browser and comes back with whatever the caller put in it. The cap is taken
 * from the order line instead.
 */

test('a raised quantity_max does not buy a larger delivery', function () {
    [$order, $orderProduct] = orderReadyToDeliver();

    $component = Livewire::test(QtyDeliveryCreate::class, [
        'fromModelType' => 'order', 'fromModelId' => $order->id,
    ]);

    $component->set('products', [[
        'order_product_id' => $orderProduct->id,
        'quantity' => 5,
        'quantity_max' => 9999,
    ]]);

    $component->call('save')->assertHasErrors('products.0.quantity');

    expect(DeliveryProduct::count())->toBe(0);
});

test('a missing quantity_max does not lift the cap', function () {
    [$order, $orderProduct] = orderReadyToDeliver();

    $component = Livewire::test(QtyDeliveryCreate::class, [
        'fromModelType' => 'order', 'fromModelId' => $order->id,
    ]);

    $component->set('products', [[
        'order_product_id' => $orderProduct->id,
        'quantity' => 5,
    ]]);

    $component->call('save')->assertHasErrors('products.0.quantity');

    expect(DeliveryProduct::count())->toBe(0);
});

test('a delivery already raised comes off the remaining quantity', function () {
    [$order, $orderProduct] = orderReadyToDeliver();

    $delivered = Delivery::create(['order_id' => $order->id]);
    $delivered->deliveryProducts()->create([
        'order_product_id' => $orderProduct->id,
        'quantity' => 3,
    ]);

    $component = Livewire::test(QtyDeliveryCreate::class, [
        'fromModelType' => 'order', 'fromModelId' => $order->id,
    ]);

    // 0.5 of the 3.5 is left, whatever the row claims.
    $component->set('products', [[
        'order_product_id' => $orderProduct->id,
        'quantity' => 1,
        'quantity_max' => 3.5,
    ]]);

    $component->call('save')->assertHasErrors('products.0.quantity');

    $component->set('products.0.quantity', 0.5);

    $component->call('save')->assertHasNoErrors();

    expect(DeliveryProduct::count())->toBe(2);
});

test('a line on a deleted delivery gives its quantity back', function () {
    [$order, $orderProduct] = orderReadyToDeliver();

    $delivered = Delivery::create(['order_id' => $order->id]);
    $delivered->deliveryProducts()->create([
        'order_product_id' => $orderProduct->id,
        'quantity' => 3,
    ]);

    $delivered->delete();

    $component = Livewire::test(QtyDeliveryCreate::class, [
        'fromModelType' => 'order', 'fromModelId' => $order->id,
    ]);

    $component->set('products', [[
        'order_product_id' => $orderProduct->id,
        'quantity' => 3.5,
    ]]);

    $component->call('save')->assertHasNoErrors();
});

/*
 * Issue #135: the edit form showed the delivery's quantities but the save
 * never wrote them back, and its rows carried no order line to cap against.
 */

class QtyDeliveryEdit extends DeliveryEdit
{
    public function render()
    {
        return '<div></div>';
    }
}

function deliveryAgainst(Order $order, OrderProduct $orderProduct, float $quantity): array
{
    $delivery = Delivery::create(['order_id' => $order->id]);
    $deliveryProduct = $delivery->deliveryProducts()->create([
        'order_product_id' => $orderProduct->id,
        'quantity' => $quantity,
    ]);

    return [$delivery, $deliveryProduct];
}

function editRow(DeliveryProduct $deliveryProduct, $quantity): array
{
    return [
        'delivery_product_id' => $deliveryProduct->id,
        'order_product_id' => $deliveryProduct->order_product_id,
        'quantity' => $quantity,
    ];
}

test('editing a delivery saves the changed quantity', function () {
    [$order, $orderProduct] = orderReadyToDeliver();
    [$delivery, $deliveryProduct] = deliveryAgainst($order, $orderProduct, 2.5);

    Livewire::test(QtyDeliveryEdit::class, ['delivery' => $delivery])
        ->set('products', [editRow($deliveryProduct, 1.25)])
        ->call('save')
        ->assertHasNoErrors();

    expect($deliveryProduct->fresh()->quantity)->toBe(1.25)
        ->and($orderProduct->deliveryRemainder())->toBe(2.25);
});

test('an edited delivery cannot exceed what the order line has left', function () {
    [$order, $orderProduct] = orderReadyToDeliver();
    deliveryAgainst($order, $orderProduct, 2);
    [$delivery, $deliveryProduct] = deliveryAgainst($order, $orderProduct, 1);

    // 1.5 is all this delivery can hold.
    Livewire::test(QtyDeliveryEdit::class, ['delivery' => $delivery])
        ->set('products', [editRow($deliveryProduct, 2) + ['quantity_max' => 9999]])
        ->call('save')
        ->assertHasErrors('products.0.quantity');

    expect($deliveryProduct->fresh()->quantity)->toEqual(1);
});

test('an edited delivery does not count its own quantity against itself', function () {
    [$order, $orderProduct] = orderReadyToDeliver();
    [$delivery, $deliveryProduct] = deliveryAgainst($order, $orderProduct, 3);

    Livewire::test(QtyDeliveryEdit::class, ['delivery' => $delivery])
        ->set('products', [editRow($deliveryProduct, 3)])
        ->call('save')
        ->assertHasNoErrors();

    Livewire::test(QtyDeliveryEdit::class, ['delivery' => $delivery])
        ->set('products', [editRow($deliveryProduct, 3.5)])
        ->call('save')
        ->assertHasNoErrors();

    expect($deliveryProduct->fresh()->quantity)->toBe(3.5);
});

test('an unraised delivery line saves from the edit form after its order line was reduced', function () {
    [$order, $orderProduct] = orderReadyToDeliver();
    [$delivery, $deliveryProduct] = deliveryAgainst($order, $orderProduct, 3);

    $orderProduct->update(['quantity' => 2]);

    // The rows exactly as the edit form's line items hand them over.
    $rows = Livewire::test(ModelProducts::class, [
        'model' => $delivery, 'creating' => 'Delivery',
    ])->get('products');

    Livewire::test(QtyDeliveryEdit::class, ['delivery' => $delivery])
        ->set('products', $rows)
        ->call('save')
        ->assertHasNoErrors();

    Livewire::test(QtyDeliveryEdit::class, ['delivery' => $delivery])
        ->set('products', [editRow($deliveryProduct, 2.5)])
        ->call('save')
        ->assertHasNoErrors();

    expect($deliveryProduct->fresh()->quantity)->toBe(2.5);

    // Raising it is still capped.
    Livewire::test(QtyDeliveryEdit::class, ['delivery' => $delivery])
        ->set('products', [editRow($deliveryProduct, 3)])
        ->call('save')
        ->assertHasErrors('products.0.quantity');
});

test('lowering an over-delivered line on the edit form is not clamped to the order remainder', function () {
    [$order, $orderProduct] = orderReadyToDeliver();
    [$delivery] = deliveryAgainst($order, $orderProduct, 3);

    $orderProduct->update(['quantity' => 2]);

    $component = Livewire::test(ModelProducts::class, [
        'model' => $delivery, 'creating' => 'Delivery',
    ]);

    // The line can hold what it already has, not just the order's 2.
    expect($component->get('products')[0]['quantity_max'])->toEqual(3);

    $component->set('products.0.quantity', 2.5)->assertHasNoErrors();
    expect($component->get('products')[0]['quantity'])->toBe(2.5);

    // Raising it past what it holds is still clamped.
    $component->set('products.0.quantity', 3.5)->assertHasErrors('products.0.quantity');
    expect($component->get('products')[0]['quantity'])->toEqual(3);
});

test('a delivery line id forged onto the create form is still capped', function () {
    [$order, $orderProduct] = orderReadyToDeliver();
    [, $deliveryProduct] = deliveryAgainst($order, $orderProduct, 3);

    $orderProduct->update(['quantity' => 2]);

    // 2.5 is within the forged line's saved 3, but a create form has no
    // existing lines, so it is held to the order line's 2.
    Livewire::test(QtyDeliveryCreate::class, [
        'fromModelType' => 'order', 'fromModelId' => $order->id,
    ])
        ->set('products', [[
            'order_product_id' => $orderProduct->id,
            'delivery_product_id' => $deliveryProduct->id,
            'quantity' => 2.5,
        ]])
        ->call('save')
        ->assertHasErrors('products.0.quantity');
});

test('the delivery edit rows carry the order line and its remaining cap', function () {
    [$order, $orderProduct] = orderReadyToDeliver();
    deliveryAgainst($order, $orderProduct, 2);
    [$delivery, $deliveryProduct] = deliveryAgainst($order, $orderProduct, 1);

    $row = Livewire::test(ModelProducts::class, [
        'model' => $delivery, 'creating' => 'Delivery',
    ])->get('products')[0];

    expect($row['delivery_product_id'])->toBe($deliveryProduct->id)
        ->and($row['order_product_id'])->toBe($orderProduct->id)
        ->and($row['quantity_max'])->toBe(1.5);
});

test('saveWithProducts uses the captured fractional quantity instead of the parent preview', function () {
    [$order, $line] = orderReadyToDeliver();
    $line->update(['quantity' => 2.5]);
    deliveryAgainst($order, $line, 1.25);

    Livewire::test(QtyDeliveryCreate::class, ['fromModelType' => 'order', 'fromModelId' => $order->id])
        ->set('products', [['order_product_id' => $line->id, 'quantity' => 1.25]])
        ->call('saveWithProducts', ['products' => [['order_product_id' => $line->id, 'quantity' => '0.5']]])
        ->assertHasNoErrors();

    expect($line->deliveryProducts()->orderBy('id')->pluck('quantity')->all())->toBe([1.25, 0.5])
        ->and($line->fresh()->deliveryRemainder())->toBe(0.75);
});

test('saveWithProducts validates the payload rather than a valid stale parent', function ($quantity) {
    [$order, $line] = orderReadyToDeliver();

    Livewire::test(QtyDeliveryCreate::class, ['fromModelType' => 'order', 'fromModelId' => $order->id])
        ->set('products', [['order_product_id' => $line->id, 'quantity' => 1]])
        ->call('saveWithProducts', ['products' => [['order_product_id' => $line->id, 'quantity' => $quantity, 'quantity_max' => 999]]])
        ->assertHasErrors('products.0.quantity');

    expect(Delivery::count())->toBe(0);
})->with([-1, 1.2345, 99]);

test('saveWithProducts preserves delivery edit line identity and updates its quantity', function () {
    [$order, $line] = orderReadyToDeliver();
    [$delivery, $deliveryLine] = deliveryAgainst($order, $line, 2.5);

    Livewire::test(QtyDeliveryEdit::class, ['delivery' => $delivery])
        ->set('products', [editRow($deliveryLine, 2.5)])
        ->call('saveWithProducts', ['products' => [editRow($deliveryLine, '0.5')]])
        ->assertHasNoErrors();

    expect($deliveryLine->fresh()->quantity)->toBe(0.5)
        ->and($delivery->deliveryProducts()->count())->toBe(1);
});

test('saveWithProducts still authorizes delivery creation', function () {
    [$order, $line] = orderReadyToDeliver();
    $this->actingAsUserWithPermissions(['view crm deliveries']);

    Livewire::test(QtyDeliveryCreate::class, ['fromModelType' => 'order', 'fromModelId' => $order->id])
        ->call('saveWithProducts', ['products' => [['order_product_id' => $line->id, 'quantity' => 0.5]]])
        ->assertForbidden();

    expect(Delivery::count())->toBe(0);
});
