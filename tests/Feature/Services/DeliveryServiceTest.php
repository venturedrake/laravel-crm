<?php

/*
 * Issue #115: the delivery form prefills the country, so an address left blank
 * used to be saved as one that was only a country.
 */

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\AddressType;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Services\DeliveryService;

beforeEach(function () {
    $this->actingAsUser();

    $this->shippingTypeId = AddressType::firstOrCreate(['name' => 'Shipping'])->id;
    $this->order = Order::create(['currency' => 'USD']);
});

function deliveryShippingAddress(array $fields = [], $id = null): array
{
    return ['shipping' => $fields + [
        'id' => $id, 'address_type_id' => test()->shippingTypeId, 'contact' => null, 'phone' => null,
        'line1' => null, 'line2' => null, 'line3' => null, 'city' => null, 'state' => null, 'code' => null,
        'country' => 'United States', 'primary' => 1,
    ]];
}

test('a delivery saved with a blank address gets none', function () {
    $delivery = app(DeliveryService::class)->create(new Request([
        'order_id' => $this->order->id, 'addresses' => deliveryShippingAddress(),
    ]));

    expect($delivery->addresses()->count())->toBe(0);
});

test('a delivery saved with an address keeps it', function () {
    $delivery = app(DeliveryService::class)->create(new Request([
        'order_id' => $this->order->id, 'addresses' => deliveryShippingAddress(['line1' => '1 Dock Road']),
    ]));

    expect($delivery->addresses()->sole())
        ->line1->toBe('1 Dock Road')
        ->country->toBe('United States')
        ->address_type_id->toBe($this->shippingTypeId);
});

test('a delivery saved without an address can be given one on edit', function () {
    $delivery = Delivery::create(['order_id' => $this->order->id]);

    app(DeliveryService::class)->update(new Request([
        'addresses' => deliveryShippingAddress(['city' => 'Springfield']),
    ]), $delivery);

    expect($delivery->addresses()->sole()->city)->toBe('Springfield');
});

test('blanking a delivery address on edit removes it', function () {
    $delivery = Delivery::create(['order_id' => $this->order->id]);
    $address = $delivery->addresses()->create([
        'external_id' => Str::uuid()->toString(), 'address_type_id' => $this->shippingTypeId,
        'line1' => '1 Dock Road', 'country' => 'United States',
    ]);

    app(DeliveryService::class)->update(new Request([
        // As the edit form sends it: the accessor-built label is still there.
        'addresses' => deliveryShippingAddress(['address' => ',   , United States'], $address->id),
    ]), $delivery);

    expect($delivery->addresses()->count())->toBe(0)
        ->and($address->fresh()->trashed())->toBeTrue();
});

test('editing a delivery address updates it in place', function () {
    $delivery = Delivery::create(['order_id' => $this->order->id]);
    $address = $delivery->addresses()->create([
        'external_id' => Str::uuid()->toString(), 'address_type_id' => $this->shippingTypeId,
        'line1' => '1 Dock Road', 'country' => 'United States',
    ]);

    app(DeliveryService::class)->update(new Request([
        'addresses' => deliveryShippingAddress(['line1' => '2 Dock Road'], $address->id),
    ]), $delivery);

    expect($delivery->addresses()->sole())
        ->id->toBe($address->id)
        ->line1->toBe('2 Dock Road');
});

test('an address id from another record is not updated', function () {
    $other = Delivery::create(['order_id' => $this->order->id]);
    $foreign = $other->addresses()->create([
        'external_id' => Str::uuid()->toString(), 'line1' => 'Not yours',
    ]);
    $delivery = Delivery::create(['order_id' => $this->order->id]);

    app(DeliveryService::class)->update(new Request([
        'addresses' => deliveryShippingAddress(['line1' => '1 Dock Road'], $foreign->id),
    ]), $delivery);

    expect(Address::find($foreign->id)->line1)->toBe('Not yours')
        ->and($delivery->addresses()->sole()->line1)->toBe('1 Dock Road');
});

/*
 * Issue #135: editing a delivery never wrote its line quantities back, so a
 * changed quantity was lost on save.
 */

function deliveredOrderLine(float $ordered = 3.5, float $delivered = 2.5): array
{
    $product = Product::create(['name' => 'By weight']);
    $orderProduct = test()->order->orderProducts()->create([
        'product_id' => $product->id, 'quantity' => $ordered, 'price' => 1000, 'amount' => (int) ($ordered * 1000),
    ]);

    $delivery = Delivery::create(['order_id' => test()->order->id]);
    $deliveryProduct = $delivery->deliveryProducts()->create([
        'order_product_id' => $orderProduct->id, 'quantity' => $delivered,
    ]);

    return [$orderProduct, $delivery, $deliveryProduct];
}

function updateDeliveryLines(Delivery $delivery, array $products): void
{
    app(DeliveryService::class)->update(new Request(['products' => $products]), $delivery);
}

test('editing a delivery persists its line quantities', function () {
    [$orderProduct, $delivery, $deliveryProduct] = deliveredOrderLine(3.5, 2.5);

    $otherOrderProduct = $this->order->orderProducts()->create([
        'product_id' => $orderProduct->product_id, 'quantity' => 4, 'price' => 1000, 'amount' => 4000,
    ]);
    $otherLine = $delivery->deliveryProducts()->create([
        'order_product_id' => $otherOrderProduct->id, 'quantity' => 3,
    ]);

    updateDeliveryLines($delivery, [
        ['delivery_product_id' => $deliveryProduct->id, 'quantity' => 1.25],
        ['delivery_product_id' => $otherLine->id, 'quantity' => 1],
    ]);

    expect($deliveryProduct->fresh()->quantity)->toBe(1.25)
        ->and($otherLine->fresh()->quantity)->toEqual(1);
});

test('the order remaining quantity follows a delivery edit', function () {
    [$orderProduct, $delivery, $deliveryProduct] = deliveredOrderLine(3.5, 3.5);

    expect($orderProduct->deliveryRemainder())->toEqual(0)
        ->and($this->order->fresh()->deliveryComplete())->toBeTrue();

    updateDeliveryLines($delivery, [
        ['delivery_product_id' => $deliveryProduct->id, 'quantity' => 1.25],
    ]);

    expect($orderProduct->deliveryRemainder())->toBe(2.25)
        ->and($this->order->fresh()->deliveryComplete())->toBeFalse();
});

test('a zero quantity on edit removes the delivery line', function () {
    [, $delivery, $deliveryProduct] = deliveredOrderLine();

    updateDeliveryLines($delivery, [
        ['delivery_product_id' => $deliveryProduct->id, 'quantity' => 0],
    ]);

    expect($deliveryProduct->fresh()->trashed())->toBeTrue()
        ->and($delivery->deliveryProducts()->count())->toBe(0);
});

test('a blank quantity on edit leaves the delivery line alone', function () {
    [, $delivery, $deliveryProduct] = deliveredOrderLine(3.5, 2.5);

    updateDeliveryLines($delivery, [
        ['delivery_product_id' => $deliveryProduct->id, 'quantity' => ''],
    ]);

    expect($deliveryProduct->fresh())
        ->trashed()->toBeFalse()
        ->quantity->toBe(2.5);
});

test('a delivery line id from another delivery is not updated', function () {
    [, , $foreignLine] = deliveredOrderLine(3.5, 1);
    $delivery = Delivery::create(['order_id' => $this->order->id]);

    updateDeliveryLines($delivery, [
        ['delivery_product_id' => $foreignLine->id, 'quantity' => 0.5],
    ]);

    expect($foreignLine->fresh()->quantity)->toEqual(1)
        ->and($delivery->deliveryProducts()->count())->toBe(0);
});

test('a delivery line cannot be raised past what the order line has left', function () {
    [, $delivery, $deliveryProduct] = deliveredOrderLine(3.5, 2.5);

    expect(fn () => updateDeliveryLines($delivery, [
        ['delivery_product_id' => $deliveryProduct->id, 'quantity' => 4],
    ]))->toThrow(ValidationException::class);

    expect($deliveryProduct->fresh()->quantity)->toBe(2.5);
});

test('a delivery line can be raised to the order quantity less other deliveries', function () {
    [$orderProduct, $delivery, $deliveryProduct] = deliveredOrderLine(3.5, 1);

    Delivery::create(['order_id' => $this->order->id])->deliveryProducts()->create([
        'order_product_id' => $orderProduct->id, 'quantity' => 2,
    ]);

    // 1.5 is all the order line has left for this delivery.
    expect(fn () => updateDeliveryLines($delivery, [
        ['delivery_product_id' => $deliveryProduct->id, 'quantity' => 1.75],
    ]))->toThrow(ValidationException::class);

    expect($deliveryProduct->fresh()->quantity)->toEqual(1);

    updateDeliveryLines($delivery, [
        ['delivery_product_id' => $deliveryProduct->id, 'quantity' => 1.5],
    ]);

    expect($deliveryProduct->fresh()->quantity)->toBe(1.5)
        ->and($orderProduct->deliveryRemainder())->toEqual(0);
});

test('a forged order_product_id does not lift the delivery cap', function () {
    [, $delivery, $deliveryProduct] = deliveredOrderLine(3.5, 2.5);

    $roomy = $this->order->orderProducts()->create([
        'product_id' => $deliveryProduct->orderProduct->product_id, 'quantity' => 100, 'price' => 1000, 'amount' => 100000,
    ]);

    expect(fn () => updateDeliveryLines($delivery, [[
        'delivery_product_id' => $deliveryProduct->id,
        'order_product_id' => $roomy->id,
        'quantity' => 10,
        'quantity_max' => 100,
    ]]))->toThrow(ValidationException::class);

    expect($deliveryProduct->fresh())
        ->quantity->toBe(2.5)
        ->order_product_id->toBe($deliveryProduct->order_product_id);
});

test('a deleted delivery gives its quantity back on edit', function () {
    [$orderProduct, $delivery, $deliveryProduct] = deliveredOrderLine(3.5, 1);

    $deleted = Delivery::create(['order_id' => $this->order->id]);
    $deleted->deliveryProducts()->create([
        'order_product_id' => $orderProduct->id, 'quantity' => 2.5,
    ]);
    $deleted->delete();

    updateDeliveryLines($delivery, [
        ['delivery_product_id' => $deliveryProduct->id, 'quantity' => 3.5],
    ]);

    expect($deliveryProduct->fresh()->quantity)->toBe(3.5)
        ->and($orderProduct->deliveryRemainder())->toEqual(0);
});

test('an unraised line saves even when its order line was since reduced', function () {
    [$orderProduct, $delivery, $deliveryProduct] = deliveredOrderLine(3.5, 3);

    $orderProduct->update(['quantity' => 2]);

    updateDeliveryLines($delivery, [
        ['delivery_product_id' => $deliveryProduct->id, 'quantity' => 3],
    ]);

    expect($deliveryProduct->fresh()->quantity)->toEqual(3);
});
