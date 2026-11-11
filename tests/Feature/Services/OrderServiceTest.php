<?php

use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Models\AddressType;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Services\OrderService;

test('service creates an order with minimum data', function () {
    // The header totals come from the saved lines.
    $product = Product::create(['name' => 'Plain']);

    $order = app(OrderService::class)->create(new Request([
        'description' => 'd', 'currency' => 'USD', 'sub_total' => 200, 'total' => 200,
        'products' => [['id' => $product->id, 'quantity' => 2, 'unit_price' => 100, 'amount' => 200, 'comments' => null]],
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

/*
 * Issue #115: the order form prefills the country, so an address left blank
 * used to be saved as one that was only a country.
 */
function orderAddresses(array $billing = [], array $shipping = [], array $ids = []): array
{
    $blank = fn (string $type, array $fields) => $fields + [
        'id' => $ids[$type] ?? null,
        'address_type_id' => AddressType::firstOrCreate(['name' => ucfirst($type)])->id,
        'contact' => null, 'phone' => null, 'line1' => null, 'line2' => null, 'line3' => null,
        'city' => null, 'state' => null, 'code' => null, 'country' => 'United States', 'primary' => 1,
    ];

    return ['billing' => $blank('billing', $billing), 'shipping' => $blank('shipping', $shipping)];
}

test('an order saved with blank addresses gets none', function () {
    $order = app(OrderService::class)->create(new Request([
        'currency' => 'USD', 'addresses' => orderAddresses(),
    ]));

    expect($order->addresses()->count())->toBe(0);
});

test('an order saves only the addresses that were filled in', function () {
    $order = app(OrderService::class)->create(new Request([
        'currency' => 'USD', 'addresses' => orderAddresses(shipping: ['line1' => '1 Dock Road']),
    ]));

    expect($order->addresses()->sole())
        ->line1->toBe('1 Dock Road')
        ->addressType->name->toBe('Shipping');
});

test('blanking an order address on edit removes it', function () {
    $order = app(OrderService::class)->create(new Request([
        'currency' => 'USD', 'addresses' => orderAddresses(['line1' => '1 Bill St'], ['line1' => '1 Ship St']),
    ]));
    $billing = $order->addresses()->whereHas('addressType', fn ($q) => $q->where('name', 'Billing'))->sole();
    $shipping = $order->addresses()->whereHas('addressType', fn ($q) => $q->where('name', 'Shipping'))->sole();

    app(OrderService::class)->update(new Request([
        'currency' => 'USD',
        // As the edit form sends it: the accessor-built label is still there.
        'addresses' => orderAddresses(
            ['address' => '1 Bill St,   , United States', 'line1' => '1 Bill St'],
            ['address' => '1 Ship St,   , United States'],
            ['billing' => $billing->id, 'shipping' => $shipping->id],
        ),
    ]), $order->fresh());

    expect($order->addresses()->sole()->id)->toBe($billing->id)
        ->and($shipping->fresh()->trashed())->toBeTrue();
});

test('an order saved without addresses can be given one on edit', function () {
    $order = Order::create(['currency' => 'USD']);

    app(OrderService::class)->update(new Request([
        'currency' => 'USD', 'addresses' => orderAddresses(billing: ['city' => 'Springfield']),
    ]), $order);

    expect($order->addresses()->sole()->city)->toBe('Springfield');
});
