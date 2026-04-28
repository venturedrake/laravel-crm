<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Customer;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Tests\TestCase;

class CustomerTest extends TestCase
{
    public function test_customer_uses_prefixed_table_name(): void
    {
        $this->assertSame('crm_customers', (new Customer)->getTable());
    }

    public function test_creating_a_customer_assigns_uuid(): void
    {
        $customer = Customer::create(['name' => 'Acme']);
        $this->assertTrue(Str::isUuid($customer->external_id));
    }

    public function test_customer_morphs_to_a_person(): void
    {
        $person = Person::create(['first_name' => 'Bob']);
        $customer = Customer::create([
            'name' => 'Bob',
            'customerable_type' => Person::class,
            'customerable_id' => $person->id,
        ]);

        $this->assertInstanceOf(MorphTo::class, $customer->customerable());
        $this->assertTrue($customer->customerable->is($person));
    }

    public function test_customer_morphs_to_an_organization(): void
    {
        $org = Organization::create(['name' => 'Acme']);
        $customer = Customer::create([
            'name' => 'Acme',
            'customerable_type' => Organization::class,
            'customerable_id' => $org->id,
        ]);

        $this->assertTrue($customer->customerable->is($org));
    }

    public function test_customer_name_is_encrypted_when_enabled(): void
    {
        config()->set('laravel-encryptable.enabled', true);

        $customer = Customer::create(['name' => 'Encrypted Customer']);

        // raw stored value should be different from plain text
        $raw = \Illuminate\Support\Facades\DB::table('crm_customers')
            ->where('id', $customer->id)
            ->value('name');
        $this->assertNotSame('Encrypted Customer', $raw);

        // accessor still returns plain text
        $this->assertSame('Encrypted Customer', $customer->fresh()->name);
    }

    public function test_customer_uses_soft_deletes(): void
    {
        $customer = Customer::create(['name' => 'Bin']);
        $customer->delete();
        $this->assertSoftDeleted('crm_customers', ['id' => $customer->id]);
    }
}


