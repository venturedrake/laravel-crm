<?php

use VentureDrake\LaravelCrm\Models\Address;

use function VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressLine;

function makeAddress(array $attributes = []): Address
{
    return new Address(array_merge([
        'line1' => '1 Test St',
        'line2' => null,
        'line3' => null,
        'city' => 'Sydney',
        'state' => 'NSW',
        'code' => '2000',
        'country' => 'Australia',
        'latitude' => null,
        'longitude' => null,
    ], $attributes));
}

test('full address produces comma separated line', function () {
    $address = makeAddress();

    $result = addressLine($address);

    expect($result)->toContain('1 Test St')
        ->toContain('Sydney')
        ->toContain('NSW')
        ->toContain('2000')
        ->toContain('Australia');
});

test('null fields are omitted from address line', function () {
    $address = makeAddress(['line2' => null, 'line3' => null, 'state' => null]);

    $result = addressLine($address);

    $parts = array_filter(explode(', ', $result));
    foreach ($parts as $part) {
        expect(trim($part))->not->toBe('');
    }
});

test('null address returns empty string', function () {
    expect(addressLine(null))->toBe('');
});

test('empty address model returns empty string', function () {
    $address = makeAddress(['line1' => null, 'city' => null, 'state' => null, 'code' => null, 'country' => null]);
    $result = addressLine($address);

    $trimmed = trim($result, ', ');
    expect($trimmed)->toBe('');
});

test('line2 and line3 are included when set', function () {
    $address = makeAddress(['line2' => 'Suite 100', 'line3' => 'Level 2']);

    $result = addressLine($address);
    expect($result)->toContain('Suite 100')->toContain('Level 2');
});

test('address without country still renders', function () {
    $address = makeAddress(['country' => null]);

    $result = addressLine($address);
    expect($result)->toContain('1 Test St');
});
