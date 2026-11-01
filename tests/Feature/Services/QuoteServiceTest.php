<?php

use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Services\QuoteService;

test('service creates a quote with minimum data', function () {
    // The header totals come from the saved lines.
    $gst = TaxRate::create(['name' => 'GST', 'rate' => 10, 'default' => false]);
    $product = Product::create(['name' => 'Taxed', 'tax_rate_id' => $gst->id]);

    $quote = app(QuoteService::class)->create(new Request([
        'title' => 'Quote 1', 'description' => 'Some description',
        'currency' => 'USD', 'sub_total' => 100, 'tax' => 10, 'total' => 110,
        'products' => [['id' => $product->id, 'quantity' => 1, 'unit_price' => 100, 'amount' => 100, 'comments' => null]],
    ]));

    expect($quote)->toBeInstanceOf(Quote::class);
    expect($quote->title)->toBe('Quote 1');
    expect($quote->currency)->toBe('USD');
    expect((int) $quote->fresh()->getRawOriginal('subtotal'))->toBe(10000);
    expect((int) $quote->fresh()->getRawOriginal('total'))->toBe(11000);
});

test('service attaches person and organization', function () {
    $person = Person::create(['first_name' => 'Jane']);
    $org = Organization::create(['name' => 'Acme']);

    $quote = app(QuoteService::class)->create(new Request([
        'title' => 'Linked', 'currency' => 'USD',
    ]), $person, $org);

    expect($quote->person_id)->toBe($person->id);
    expect($quote->organization_id)->toBe($org->id);
});

test('service stores formatted money values on create', function () {
    $product = Product::create(['name' => 'Widget']);

    $quote = app(QuoteService::class)->create(new Request([
        'title' => 'Formatted', 'currency' => 'USD',
        'sub_total' => '2,469.12', 'tax' => '0.00', 'total' => '$2,469.12',
        'products' => [
            [
                'id' => $product->id,
                'quantity' => 2,
                'unit_price' => '$1,234.56',
                'amount' => '$2,469.12',
                'comments' => null,
            ],
        ],
    ]));

    $quoteProduct = $quote->quoteProducts()->first()->fresh();

    expect((int) $quoteProduct->getRawOriginal('price'))->toBe(123456);
    expect((int) $quoteProduct->getRawOriginal('amount'))->toBe(246912);
    expect((int) $quote->fresh()->getRawOriginal('total'))->toBe(246912);
});

test('service stores formatted money values on update', function () {
    $product = Product::create(['name' => 'Widget']);

    $quote = app(QuoteService::class)->create(new Request([
        'title' => 'Formatted', 'currency' => 'USD',
        'products' => [
            [
                'id' => $product->id,
                'quantity' => 1,
                'unit_price' => 10,
                'amount' => 10,
                'comments' => null,
            ],
        ],
    ]));

    $quoteProduct = $quote->quoteProducts()->first();

    app(QuoteService::class)->update(new Request([
        'title' => 'Formatted', 'currency' => 'USD',
        'sub_total' => '3,000.00', 'total' => '$3,000.00',
        'products' => [
            [
                'quote_product_id' => $quoteProduct->id,
                'id' => $product->id,
                'quantity' => 3,
                'unit_price' => '$1,000.00',
                'amount' => '$3,000.00',
                'comments' => null,
            ],
        ],
    ]), $quote);

    expect((int) $quoteProduct->fresh()->getRawOriginal('price'))->toBe(100000);
    expect((int) $quoteProduct->fresh()->getRawOriginal('amount'))->toBe(300000);
    expect((int) $quote->fresh()->getRawOriginal('total'))->toBe(300000);
});

test('service updates an existing quote', function () {
    $quote = Quote::create(['title' => 'Old', 'currency' => 'USD']);

    app(QuoteService::class)->update(new Request([
        'title' => 'Updated', 'description' => 'New desc',
        'currency' => 'AUD', 'sub_total' => 50, 'total' => 50,
    ]), $quote);

    $fresh = $quote->fresh();
    expect($fresh->title)->toBe('Updated');
    expect($fresh->description)->toBe('New desc');
    expect($fresh->currency)->toBe('AUD');
});
