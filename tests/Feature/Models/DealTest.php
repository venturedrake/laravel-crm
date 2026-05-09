<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Deal;

test('deal uses prefixed table', function () {
    expect((new Deal)->getTable())->toBe('crm_deals');
});

test('creating a deal assigns external id and number', function () {
    app('laravel-crm.settings')->set('deal_prefix', 'D');

    $deal = Deal::create(['title' => 'Big Deal']);

    expect(Str::isUuid($deal->external_id))->toBeTrue();
    expect($deal->number)->toBe(1000);
    expect($deal->deal_id)->toBe('D1000');
});

test('set amount attribute multiplies by one hundred', function () {
    $deal = Deal::create(['title' => 'Money', 'amount' => 99.99]);

    expect((int) $deal->fresh()->amount)->toBe(9999);
});

test('deal increments number per record', function () {
    $a = Deal::create(['title' => 'A']);
    $b = Deal::create(['title' => 'B']);

    expect($b->number)->toBe($a->number + 1);
});
