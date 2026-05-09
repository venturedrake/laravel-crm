<?php

use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Product;

use function VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\dateFormats;
use function VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\emailTypes;
use function VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\fieldModels;
use function VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel;
use function VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\phoneTypes;
use function VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\timeFormats;

test('options from model returns id keyed array', function () {
    $items = [
        (object) ['id' => 1, 'name' => 'A'],
        (object) ['id' => 2, 'name' => 'B'],
    ];

    expect(optionsFromModel($items))->toBe(['' => '', 1 => 'A', 2 => 'B']);
});

test('options from model can skip null entry', function () {
    $result = optionsFromModel([(object) ['id' => 1, 'name' => 'A']], false);

    expect($result)->not->toHaveKey('');
    expect($result[1])->toBe('A');
});

test('phone types returns expected options', function () {
    $ids = array_column(phoneTypes(false), 'id');

    expect($ids)->toContain('work')
        ->toContain('home')
        ->toContain('mobile')
        ->toContain('fax')
        ->toContain('other');
});

test('email types returns expected options', function () {
    $ids = array_column(emailTypes(false), 'id');

    expect($ids)->toContain('work')
        ->toContain('home')
        ->toContain('other')
        ->not->toContain('mobile');
});

test('date formats includes common formats', function () {
    $formats = dateFormats();

    expect($formats)->toHaveKey('Y-m-d')
        ->toHaveKey('d/m/Y')
        ->toHaveKey('m/d/Y');
});

test('time formats includes common formats', function () {
    $formats = timeFormats();

    expect($formats)->toHaveKey('g:i a')->toHaveKey('H:i');
});

test('field models lists supported entities', function () {
    $fields = fieldModels();

    expect($fields)->toHaveKey(Lead::class)
        ->toHaveKey(Deal::class)
        ->toHaveKey(Person::class)
        ->toHaveKey(Organization::class)
        ->toHaveKey(Product::class);
});
