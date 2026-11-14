<?php

use VentureDrake\LaravelCrm\Models\Customer;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;

test('a delivery title includes the formatted order total and person name', function () {
    $person = Person::create(['first_name' => 'Grace', 'last_name' => 'Hopper']);
    $order = Order::create(['currency' => 'USD', 'total' => 1234.56, 'person_id' => $person->id]);
    $delivery = Delivery::create(['order_id' => $order->id]);

    expect($delivery->fresh()->title)->toBe('$1,234.56 - Grace Hopper');
});

test('a delivery title keeps client and organization precedence over the person', function (bool $withClient) {
    $person = Person::create(['first_name' => 'Grace', 'last_name' => 'Hopper']);
    $organization = Organization::create(['name' => 'Acme Corp']);
    $order = Order::create([
        'currency' => 'USD',
        'total' => 1234.56,
        'person_id' => $person->id,
        'organization_id' => $organization->id,
        'client_id' => $withClient ? Customer::create(['name' => 'Priority Client'])->id : null,
    ]);
    $delivery = Delivery::create(['order_id' => $order->id]);

    expect($delivery->fresh()->title)->toBe('$1,234.56 - '.($withClient ? 'Priority Client' : 'Acme Corp'));
})->with(['organization' => false, 'client' => true]);

test('a delivery title keeps the legacy organization person ahead of the order person', function () {
    $organization = (new Organization)->setRelation('person', new Person(['first_name' => 'Legacy Contact']));
    $order = (new Order(['currency' => 'USD', 'total' => 1234.56]))
        ->setRelation('client', null)
        ->setRelation('organization', $organization)
        ->setRelation('person', new Person(['first_name' => 'Order Contact']));
    $delivery = (new Delivery)->setRelation('order', $order);

    expect($delivery->title)->toBe('$1,234.56 - Legacy Contact');
});

test('a delivery without an order has no title', function () {
    expect(Delivery::create()->fresh()->title)->toBeNull();
});
