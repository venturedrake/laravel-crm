<?php

use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Services\QuoteService;

test('service creates a quote with minimum data', function () {
    $quote = app(QuoteService::class)->create(new Request([
        'title' => 'Quote 1', 'description' => 'Some description',
        'currency' => 'USD', 'sub_total' => 100, 'tax' => 10, 'total' => 110,
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
