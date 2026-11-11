<?php

use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Support\AddressContent;

test('an address with only form-set fields is blank', function () {
    expect(AddressContent::isBlank([
        'id' => 7, 'address_type_id' => 1, 'country' => 'United States', 'primary' => 1,
        'line1' => null, 'line2' => '', 'line3' => '  ', 'city' => null, 'state' => null, 'code' => null,
    ]))->toBeTrue()
        ->and(AddressContent::isBlank([]))->toBeTrue();
});

test('the accessor-built label is not content', function () {
    // The edit forms mount `address` from the accessor, which for an empty
    // row is just separators and the country.
    $address = new Address(['country' => 'United States']);

    expect($address->address)->toContain('United States')
        ->and(AddressContent::isBlank(['address' => $address->address, 'country' => 'United States']))->toBeTrue()
        ->and(AddressContent::isBlank($address))->toBeTrue();
});

test('any typed field makes an address not blank', function (string $field) {
    expect(AddressContent::isBlank([$field => 'x', 'country' => 'United States']))->toBeFalse()
        ->and(AddressContent::isBlank(new Address([$field => 'x'])))->toBeFalse();
})->with(['line1', 'line2', 'line3', 'city', 'state', 'code', 'contact', 'phone', 'name']);

test('an address is deliverable with a street or a city', function (array $attributes, bool $deliverable) {
    expect(AddressContent::isDeliverable($attributes))->toBe($deliverable)
        ->and(AddressContent::isDeliverable(new Address($attributes)))->toBe($deliverable);
})->with([
    'country only' => [['country' => 'United States'], false],
    'state and code' => [['state' => 'CA', 'code' => '90210', 'country' => 'United States'], false],
    'line 2 only' => [['line2' => 'Unit 4'], false],
    'whitespace street' => [['line1' => '   '], false],
    'street' => [['line1' => '1 Dock Road'], true],
    'city' => [['city' => 'Springfield'], true],
]);
