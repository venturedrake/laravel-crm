<?php

use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Services\OrderService;

test('service creates an order with minimum data', function () {
    $order = app(OrderService::class)->create(new Request([
        'description' => 'd', 'currency' => 'USD', 'sub_total' => 200, 'total' => 200,
    ]));

    expect($order)->toBeInstanceOf(Order::class);
    expect($order->currency)->toBe('USD');
    expect((int) $order->fresh()->getRawOriginal('subtotal'))->toBe(20000);
});

test('service attaches person org and quote', function () {
    $person = Person::create(['first_name' => 'Bob']);
    $org = Organization::create(['name' => 'Acme']);
    $quote = Quote::create(['title' => 'Source quote']);

    $order = app(OrderService::class)->create(new Request([
        'currency' => 'USD', 'quote_id' => $quote->id,
    ]), $person, $org);

    expect($order->person_id)->toBe($person->id);
    expect($order->organization_id)->toBe($org->id);
    expect($order->quote_id)->toBe($quote->id);
});

test('service updates an order', function () {
    $order = Order::create(['description' => 'Old', 'currency' => 'USD']);

    app(OrderService::class)->update(new Request([
        'description' => 'New', 'reference' => 'R-1', 'currency' => 'EUR', 'sub_total' => 5, 'total' => 5,
    ]), $order);

    $fresh = $order->fresh();
    expect($fresh->description)->toBe('New');
    expect($fresh->reference)->toBe('R-1');
    expect($fresh->currency)->toBe('EUR');
});
