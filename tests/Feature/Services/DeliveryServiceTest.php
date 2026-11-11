<?php

/*
 * Issue #115: the delivery form prefills the country, so an address left blank
 * used to be saved as one that was only a country.
 */

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\AddressType;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Order;
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
