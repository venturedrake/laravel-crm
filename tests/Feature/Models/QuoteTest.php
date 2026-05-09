<?php

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Quote;

test('quote uses prefixed table name', function () {
    expect((new Quote)->getTable())->toBe('crm_quotes');

    config()->set('laravel-crm.db_table_prefix', 'foo_');
    expect((new Quote)->getTable())->toBe('foo_quotes');
});

test('creating a quote assigns external id uuid', function () {
    $quote = Quote::create(['title' => 'Q']);
    expect(Str::isUuid($quote->external_id))->toBeTrue();
});

test('creating a quote auto increments number starting from 1000', function () {
    $first = Quote::create(['title' => 'A']);
    $second = Quote::create(['title' => 'B']);

    expect($first->number)->toBe(1000);
    expect($second->number)->toBe(1001);
});

test('quote id is built from prefix plus number', function () {
    app('laravel-crm.settings')->set('quote_prefix', 'Q');

    $quote = Quote::create(['title' => 'Prefixed']);

    expect($quote->prefix)->toBe('Q');
    expect($quote->quote_id)->toBe('Q1000');
});

test('set money attributes multiply by one hundred', function () {
    $quote = Quote::create(['title' => 'Money', 'subtotal' => 100, 'discount' => 10, 'tax' => 9, 'adjustments' => 1, 'total' => 100]);

    $fresh = $quote->fresh();
    expect((int) $fresh->getRawOriginal('subtotal'))->toBe(10000);
    expect((int) $fresh->getRawOriginal('discount'))->toBe(1000);
    expect((int) $fresh->getRawOriginal('tax'))->toBe(900);
    expect((int) $fresh->getRawOriginal('adjustments'))->toBe(100);
    expect((int) $fresh->getRawOriginal('total'))->toBe(10000);
});

test('money attributes handle null', function () {
    $quote = Quote::create(['title' => 'Empty', 'subtotal' => null, 'total' => null]);

    expect($quote->fresh()->getRawOriginal('subtotal'))->toBeNull();
    expect($quote->fresh()->getRawOriginal('total'))->toBeNull();
});

test('quote uses soft deletes', function () {
    $quote = Quote::create(['title' => 'Soft delete']);
    $quote->delete();
    $this->assertSoftDeleted('crm_quotes', ['id' => $quote->id]);
});

test('quote relationships are defined', function () {
    $quote = new Quote;

    expect($quote->person())->toBeInstanceOf(BelongsTo::class);
    expect($quote->organization())->toBeInstanceOf(BelongsTo::class);
    expect($quote->deal())->toBeInstanceOf(BelongsTo::class);
    expect($quote->ownerUser())->toBeInstanceOf(BelongsTo::class);
    expect($quote->pipeline())->toBeInstanceOf(BelongsTo::class);
    expect($quote->pipelineStage())->toBeInstanceOf(BelongsTo::class);
    expect($quote->quoteProducts())->toBeInstanceOf(HasMany::class);
    expect($quote->orders())->toBeInstanceOf(HasMany::class);
    expect($quote->labels())->toBeInstanceOf(MorphToMany::class);
});

test('quote belongs to person and organization', function () {
    $person = Person::create(['first_name' => 'Alice']);
    $org = Organization::create(['name' => 'Acme']);

    $quote = Quote::create(['title' => 'With links', 'person_id' => $person->id, 'organization_id' => $org->id]);

    expect($quote->person->is($person))->toBeTrue();
    expect($quote->organization->is($org))->toBeTrue();
});
