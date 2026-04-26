<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Models;

use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Tests\TestCase;

class AddressTest extends TestCase
{
    public function test_address_uses_prefixed_table(): void
    {
        $this->assertSame('crm_addresses', (new Address)->getTable());
    }

    public function test_organization_can_have_addresses(): void
    {
        $org = Organization::create(['name' => 'Acme']);

        $address = $org->addresses()->create([
            'line1' => '1 Main St',
            'city' => 'Sydney',
            'state' => 'NSW',
            'code' => '2000',
            'country' => 'Australia',
            'primary' => true,
        ]);

        $this->assertInstanceOf(Address::class, $address);
        $this->assertSame($org->id, $address->addressable->id);
        $this->assertSame($address->id, $org->getPrimaryAddress()->id);
    }
}
