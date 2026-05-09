<?php

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Quote;

test('order uses prefixed table name', function () {
    expect((new Order)->getTable())->toBe('crm_orders');
});

test('creating an order assigns uuid external id', function () {
    $order = Order::create(['title' => 'O']);
    expect(Str::isUuid($order->external_id))->toBeTrue();
});

test('creating an order auto increments number starting from 1000', function () {
    $first = Order::create(['title' => 'A']);
    $second = Order::create(['title' => 'B']);

    expect($first->number)->toBe(1000);
    expect($second->number)->toBe(1001);
});

test('order id is built from prefix plus number', function () {
    app('laravel-crm.settings')->set('order_prefix', 'O');

    $order = Order::create(['title' => 'X']);

    expect($order->prefix)->toBe('O');
    expect($order->order_id)->toBe('O1000');
});

test('set money attributes multiply by one hundred', function () {
    $order = Order::create(['title' => 'Money', 'subtotal' => 50, 'discount' => 5, 'tax' => 4.50, 'adjustments' => 0, 'total' => 49.50]);

    $fresh = $order->fresh();
    expect((int) $fresh->getRawOriginal('subtotal'))->toBe(5000);
    expect((int) $fresh->getRawOriginal('discount'))->toBe(500);
    expect((int) $fresh->getRawOriginal('tax'))->toBe(450);
    expect((int) $fresh->getRawOriginal('adjustments'))->toBe(0);
    expect((int) $fresh->getRawOriginal('total'))->toBe(4950);
});

test('order relationships are defined', function () {
    $order = new Order;

    expect($order->person())->toBeInstanceOf(BelongsTo::class);
    expect($order->organization())->toBeInstanceOf(BelongsTo::class);
    expect($order->deal())->toBeInstanceOf(BelongsTo::class);
    expect($order->quote())->toBeInstanceOf(BelongsTo::class);
    expect($order->orderProducts())->toBeInstanceOf(HasMany::class);
    expect($order->invoices())->toBeInstanceOf(HasMany::class);
    expect($order->deliveries())->toBeInstanceOf(HasMany::class);
    expect($order->purchaseOrders())->toBeInstanceOf(HasMany::class);
    expect($order->labels())->toBeInstanceOf(MorphToMany::class);
});

test('order uses soft deletes', function () {
    $order = Order::create(['title' => 'Bin me']);
    $order->delete();
    $this->assertSoftDeleted('crm_orders', ['id' => $order->id]);
});

test('order belongs to quote', function () {
    $quote = Quote::create(['title' => 'Q']);
    $order = Order::create(['title' => 'From Q', 'quote_id' => $quote->id]);

    expect($order->quote->is($quote))->toBeTrue();
});
