<?php

/*
 * remove_country_only_addresses_on_laravel_crm_addresses_table - removes the
 * order and delivery addresses the services used to save from a blank form,
 * which held nothing but the prefilled country (issue #115).
 */

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Setting;

function runCountryOnlyAddressCleanup(): void
{
    $migration = require __DIR__.'/../../database/updates/2026_11_11_000001_remove_country_only_addresses_on_laravel_crm_addresses_table.php';

    $migration->up();
}

function addressOn($model, array $attributes): Address
{
    return $model->addresses()->create($attributes + ['external_id' => Str::uuid()->toString()]);
}

test('country-only order and delivery addresses are removed', function () {
    $order = Order::create(['currency' => 'USD']);
    $delivery = Delivery::create(['order_id' => $order->id]);

    $orderBlank = addressOn($order, ['country' => 'United States']);
    $deliveryBlank = addressOn($delivery, ['country' => 'United States', 'address' => ',   , United States']);

    runCountryOnlyAddressCleanup();

    expect(Address::withTrashed()->find($orderBlank->id)->trashed())->toBeTrue()
        ->and(Address::withTrashed()->find($deliveryBlank->id)->trashed())->toBeTrue();
});

test('addresses with content, and ones not on an order or delivery, are left alone', function () {
    $order = Order::create(['currency' => 'USD']);
    $delivery = Delivery::create(['order_id' => $order->id]);
    $team = Setting::updateOrCreate(['name' => 'team'], ['value' => 'related']);
    $purchaseOrder = PurchaseOrder::create(['currency' => 'USD', 'delivery_type' => 'deliver']);

    $kept = [
        addressOn($order, ['line1' => '1 Dock Road', 'country' => 'United States']),
        addressOn($delivery, ['contact' => 'Pat', 'country' => 'United States']),
        addressOn($team, ['country' => 'United States']),
        $purchaseOrder->address()->create(['external_id' => Str::uuid()->toString(), 'country' => 'United States']),
    ];

    runCountryOnlyAddressCleanup();

    foreach ($kept as $address) {
        expect($address->fresh()->trashed())->toBeFalse();
    }
});

test('running it twice changes nothing more', function () {
    $order = Order::create(['currency' => 'USD']);
    $blank = addressOn($order, ['country' => 'United States']);

    runCountryOnlyAddressCleanup();
    $deletedAt = Address::withTrashed()->find($blank->id)->deleted_at;

    $this->travel(1)->day();
    runCountryOnlyAddressCleanup();

    expect(Address::withTrashed()->find($blank->id)->deleted_at->equalTo($deletedAt))->toBeTrue();
});
