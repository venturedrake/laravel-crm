<?php

use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\InvoiceService;

beforeEach(function () {
    $this->actingAsUser();
});

test('service creates an invoice with minimum data', function () {
    $invoice = app(InvoiceService::class)->create(new Request([
        'reference' => 'INV-1', 'currency' => 'USD',
        'issue_date' => '2025-01-01', 'due_date' => '2025-01-31',
        'sub_total' => 100, 'tax' => 10, 'total' => 110,
    ]));

    expect($invoice)->toBeInstanceOf(Invoice::class);
    expect($invoice->reference)->toBe('INV-1');
    expect((int) $invoice->fresh()->getRawOriginal('total'))->toBe(11000);
});

test('service attaches person org and order', function () {
    $person = Person::create(['first_name' => 'C']);
    $org = Organization::create(['name' => 'Acme']);
    $order = Order::create(['description' => 'Order']);

    $invoice = app(InvoiceService::class)->create(new Request([
        'currency' => 'USD',
        'order_id' => $order->id,
    ]), $person, $org);

    expect($invoice->person_id)->toBe($person->id);
    expect($invoice->organization_id)->toBe($org->id);
    expect($invoice->order_id)->toBe($order->id);
});

test('service updates an invoice', function () {
    $invoice = Invoice::create(['currency' => 'USD']);

    app(InvoiceService::class)->update(new Request([
        'reference' => 'NEW', 'currency' => 'GBP',
        'sub_total' => 1, 'tax' => 0, 'total' => 1,
    ]), $invoice);

    $fresh = $invoice->fresh();
    expect($fresh->reference)->toBe('NEW');
    expect($fresh->currency)->toBe('GBP');
});
