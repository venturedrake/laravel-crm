<?php

/*
 * Issue #115: orders and deliveries saved with their address left blank get
 * none, so their edit forms must start an empty slot with the type and default
 * country the create forms give it - otherwise one filled in later is saved
 * with no type.
 */

use Illuminate\Support\Str;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Deliveries\DeliveryCreate;
use VentureDrake\LaravelCrm\Livewire\Deliveries\DeliveryEdit;
use VentureDrake\LaravelCrm\Livewire\Orders\OrderEdit;
use VentureDrake\LaravelCrm\Models\AddressType;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Setting;

beforeEach(function () {
    $this->actingAsUser();

    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);
    Setting::updateOrCreate(['name' => 'team'], ['value' => 'related']);
    Setting::updateOrCreate(['name' => 'country'], ['value' => 'Australia']);
    app('laravel-crm.settings')->forgetCache();

    foreach ([Order::class, Invoice::class, Delivery::class] as $model) {
        Pipeline::create([
            'external_id' => Str::uuid()->toString(), 'name' => class_basename($model), 'model' => $model,
        ])->pipelineStages()->create(['external_id' => Str::uuid()->toString(), 'name' => 'Draft', 'order' => 0]);
    }

    $this->billingTypeId = AddressType::firstOrCreate(['name' => 'Billing'])->id;
    $this->shippingTypeId = AddressType::firstOrCreate(['name' => 'Shipping'])->id;

    $this->order = Order::create(['currency' => 'USD']);
});

test('the order edit form gives an empty address slot its type and country', function () {
    Livewire::test(OrderEdit::class, ['order' => $this->order])
        ->assertSet('addresses.billing.address_type_id', $this->billingTypeId)
        ->assertSet('addresses.billing.country', 'Australia')
        ->assertSet('addresses.shipping.address_type_id', $this->shippingTypeId)
        ->assertSet('addresses.shipping.country', 'Australia');
});

test('the order edit form leaves a saved address as it is', function () {
    $this->order->addresses()->create([
        'external_id' => Str::uuid()->toString(), 'address_type_id' => $this->shippingTypeId,
        'line1' => '1 Dock Road', 'country' => 'New Zealand',
    ]);

    Livewire::test(OrderEdit::class, ['order' => $this->order->fresh()])
        ->assertSet('addresses.shipping.country', 'New Zealand')
        ->assertSet('addresses.billing.country', 'Australia');
});

test('a delivery created with a blank address has none, and can be given one on edit', function () {
    Livewire::test(DeliveryCreate::class, ['fromModelType' => 'order', 'fromModelId' => $this->order->id])
        ->set('products', [])
        ->call('save')
        ->assertHasNoErrors();

    $delivery = Delivery::sole();

    expect($delivery->addresses()->count())->toBe(0);

    Livewire::test(DeliveryEdit::class, ['delivery' => $delivery])
        ->assertSet('addresses.shipping.address_type_id', $this->shippingTypeId)
        ->assertSet('addresses.shipping.country', 'Australia')
        ->set('addresses.shipping.line1', '1 Dock Road')
        ->call('save')
        ->assertHasNoErrors();

    expect($delivery->addresses()->sole())
        ->line1->toBe('1 Dock Road')
        ->country->toBe('Australia')
        ->address_type_id->toBe($this->shippingTypeId);
});
