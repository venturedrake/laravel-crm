<?php

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Order;

test('invoice uses prefixed table name', function () {
    expect((new Invoice)->getTable())->toBe('crm_invoices');
});

test('creating an invoice assigns uuid', function () {
    $invoice = Invoice::create([]);
    expect(Str::isUuid($invoice->external_id))->toBeTrue();
});

test('invoice observer increments number and builds invoice id', function () {
    app('laravel-crm.settings')->set('invoice_prefix', 'INV');

    $first = Invoice::create([]);
    $second = Invoice::create([]);

    expect((int) $first->number)->toBe(1000);
    expect((int) $second->number)->toBe(1001);
    expect($first->prefix)->toBe('INV');
    expect($first->invoice_id)->toBe('INV1000');
    expect($second->invoice_id)->toBe('INV1001');
});

test('set money attributes multiply by one hundred', function () {
    $invoice = Invoice::create(['subtotal' => 200, 'tax' => 20, 'total' => 220, 'amount_due' => 220, 'amount_paid' => 50]);

    $fresh = $invoice->fresh();
    expect((int) $fresh->getRawOriginal('subtotal'))->toBe(20000);
    expect((int) $fresh->getRawOriginal('tax'))->toBe(2000);
    expect((int) $fresh->getRawOriginal('total'))->toBe(22000);
    expect((int) $fresh->getRawOriginal('amount_due'))->toBe(22000);
    expect((int) $fresh->getRawOriginal('amount_paid'))->toBe(5000);
});

test('amount due accessor returns raw stored value', function () {
    $invoice = Invoice::create(['amount_due' => 150]);

    expect((int) $invoice->fresh()->amount_due)->toBe(15000);
});

test('invoice relationships are defined', function () {
    $invoice = new Invoice;

    expect($invoice->order())->toBeInstanceOf(BelongsTo::class);
    expect($invoice->person())->toBeInstanceOf(BelongsTo::class);
    expect($invoice->organization())->toBeInstanceOf(BelongsTo::class);
    expect($invoice->invoiceLines())->toBeInstanceOf(HasMany::class);
    expect($invoice->labels())->toBeInstanceOf(MorphToMany::class);
});

test('invoice belongs to order', function () {
    $order = Order::create(['title' => 'O1']);
    $invoice = Invoice::create(['order_id' => $order->id]);

    expect($invoice->order->is($order))->toBeTrue();
});

test('invoice uses soft deletes', function () {
    $invoice = Invoice::create([]);
    $invoice->delete();
    $this->assertSoftDeleted('crm_invoices', ['id' => $invoice->id]);
});

test('invoice is auditable', function () {
    expect(new Invoice)->toBeInstanceOf(Auditable::class);
});
