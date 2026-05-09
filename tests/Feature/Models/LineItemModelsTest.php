<?php

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\InvoiceLine;
use VentureDrake\LaravelCrm\Models\OrderProduct;
use VentureDrake\LaravelCrm\Models\QuoteProduct;

test('quote product table uuid relations and money', function () {
    $line = QuoteProduct::create(['quote_id' => 1, 'product_id' => 1, 'quantity' => 2, 'price' => 12.50, 'amount' => 25.00]);

    expect($line->getTable())->toBe('crm_quote_products');
    expect(Str::isUuid($line->external_id))->toBeTrue();
    expect((int) $line->fresh()->getRawOriginal('price'))->toBe(1250);
    expect((int) $line->fresh()->getRawOriginal('amount'))->toBe(2500);
    expect($line->quote())->toBeInstanceOf(BelongsTo::class);
    expect($line->product())->toBeInstanceOf(BelongsTo::class);

    $line->delete();
    $this->assertSoftDeleted('crm_quote_products', ['id' => $line->id]);
});

test('order product table uuid relations and money', function () {
    $line = OrderProduct::create(['order_id' => 1, 'product_id' => 1, 'quantity' => 3, 'price' => 5.25, 'amount' => 15.75]);

    expect($line->getTable())->toBe('crm_order_products');
    expect(Str::isUuid($line->external_id))->toBeTrue();
    expect((int) $line->fresh()->getRawOriginal('price'))->toBe(525);
    expect((int) $line->fresh()->getRawOriginal('amount'))->toBe(1575);
    expect($line->order())->toBeInstanceOf(BelongsTo::class);
    expect($line->product())->toBeInstanceOf(BelongsTo::class);
});

test('invoice line table uuid relations and money', function () {
    $line = InvoiceLine::create(['invoice_id' => 1, 'product_id' => 1, 'quantity' => 1, 'price' => 99.99, 'tax_amount' => 10.00, 'amount' => 99.99]);

    expect($line->getTable())->toBe('crm_invoice_lines');
    expect(Str::isUuid($line->external_id))->toBeTrue();
    expect((int) $line->fresh()->getRawOriginal('price'))->toBe(9999);
    expect((int) $line->fresh()->getRawOriginal('tax_amount'))->toBe(1000);
    expect((int) $line->fresh()->getRawOriginal('amount'))->toBe(9999);
    expect($line->invoice())->toBeInstanceOf(BelongsTo::class);
    expect($line->product())->toBeInstanceOf(BelongsTo::class);
});
