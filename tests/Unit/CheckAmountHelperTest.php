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

/*
 * Decimal quantities. Price is integer cents, so a fractional line computes a
 * half-cent remainder that an exact == would flag as a mismatch on every
 * show page and index.
 */

test('line amount accepts a fractional quantity within half a cent', function () {
    // $9.99 x 3.5 is 3496.5 cents; the stored amount rounds to 3497.
    $item = new stdClass;
    $item->price = 999;
    $item->quantity = 3.5;
    $item->amount = 3497;

    expect(lineAmount($item))->toBeTrue();
});

test('line amount still rejects a fractional quantity priced wrong', function () {
    $item = new stdClass;
    $item->price = 1000;
    $item->quantity = 3.5;
    $item->amount = 3000;

    expect(lineAmount($item))->toBeFalse();
});

test('line amount reads the string shape a decimal column returns', function () {
    $item = new stdClass;
    $item->price = 1000;
    $item->quantity = '3.500';
    $item->amount = 3500;

    expect(lineAmount($item))->toBeTrue();
});

test('line amount agrees with the form on a line exactly on the half cent', function () {
    // $333.33 x 2.5 is exactly 83332.5 cents, which the form now saves as 83333.
    $item = new stdClass;
    $item->price = 33333;
    $item->quantity = 2.5;
    $item->amount = 83333;

    expect(lineAmount($item))->toBeTrue();
});

test('line amount rejects the old round-down of a half-cent line', function () {
    $item = new stdClass;
    $item->price = 33333;
    $item->quantity = 2.5;
    $item->amount = 83332;

    expect(lineAmount($item))->toBeFalse();
});
