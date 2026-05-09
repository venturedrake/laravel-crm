<?php

use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\Organization;

test('address uses prefixed table', function () {
    expect((new Address)->getTable())->toBe('crm_addresses');
});

test('organization can have addresses', function () {
    $org = Organization::create(['name' => 'Acme']);

    $address = $org->addresses()->create([
        'line1' => '1 Main St', 'city' => 'Sydney', 'state' => 'NSW',
        'code' => '2000', 'country' => 'Australia', 'primary' => true,
    ]);

    expect($address)->toBeInstanceOf(Address::class);
    expect($address->addressable->id)->toBe($org->id);
    expect($org->getPrimaryAddress()->id)->toBe($address->id);
});
