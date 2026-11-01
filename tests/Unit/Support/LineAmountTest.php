<?php

use VentureDrake\LaravelCrm\Support\LineAmount;

test('a line exactly on the half cent rounds up', function () {
    // 33333 x 2.5 is exactly 83332.5 cents. Multiplying float dollars gave
    // 833.3249999... and rounded down to 833.32.
    expect(LineAmount::amountCents(33333, 2.5))->toBe(83333);
});

test('a fractional quantity rounds half up to the cent', function () {
    expect(LineAmount::amountCents(999, 3.5))->toBe(3497)
        ->and(LineAmount::amountCents(999, 0.5))->toBe(500);
});

test('it reads the string shapes a quantity arrives in', function () {
    expect(LineAmount::amountCents(1000, '3.500'))->toBe(3500)
        ->and(LineAmount::amountCents(1000, null))->toBe(0)
        ->and(LineAmount::amountCents(1000, 0))->toBe(0);
});

test('tax rounds to the cent', function () {
    expect(LineAmount::taxCents(83333, 10))->toBe(8333)
        ->and(LineAmount::taxCents(3497, 10))->toBe(350)
        ->and(LineAmount::taxCents(1000, 0))->toBe(0)
        ->and(LineAmount::taxCents(1001, 12.5))->toBe(125);
});

test('the form helper takes masked dollar prices', function () {
    expect(LineAmount::forForm('333.33', 2.5, 10))->toBe(['amount' => 83333, 'tax' => 8333])
        ->and(LineAmount::forForm('1,200.00', 4, 10))->toBe(['amount' => 480000, 'tax' => 48000])
        ->and(LineAmount::forForm(null, 4, null))->toBe(['amount' => 0, 'tax' => 0]);
});
