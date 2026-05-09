<?php

use function VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\lineAmount;

test('line amount returns true when price times quantity equals amount', function () {
    $item = new stdClass;
    $item->price = 10;
    $item->quantity = 3;
    $item->amount = 30;

    expect(lineAmount($item))->toBeTrue();
});

test('line amount returns false when amount does not equal price times quantity', function () {
    $item = new stdClass;
    $item->price = 10;
    $item->quantity = 3;
    $item->amount = 25;

    expect(lineAmount($item))->toBeFalse();
});

test('line amount handles float values', function () {
    $item = new stdClass;
    $item->price = 9.99;
    $item->quantity = 2;
    $item->amount = 19.98;

    expect(lineAmount($item))->toBeTrue();
});

test('line amount returns true for zero quantities', function () {
    $item = new stdClass;
    $item->price = 100;
    $item->quantity = 0;
    $item->amount = 0;

    expect(lineAmount($item))->toBeTrue();
});

test('line amount returns false when amount is null', function () {
    $item = new stdClass;
    $item->price = 10;
    $item->quantity = 2;
    $item->amount = null;

    expect(lineAmount($item))->toBeFalse();
});

test('line amount returns false when price is null', function () {
    $item = new stdClass;
    $item->price = null;
    $item->quantity = 2;
    $item->amount = 0;

    expect(lineAmount($item))->toBeFalse();
});

test('line amount returns false when quantity is null', function () {
    $item = new stdClass;
    $item->price = 10;
    $item->quantity = null;
    $item->amount = 0;

    expect(lineAmount($item))->toBeFalse();
});
