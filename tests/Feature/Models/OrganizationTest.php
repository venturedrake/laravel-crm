<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Models;

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Tests\TestCase;

class OrganizationTest extends TestCase
{
    public function test_organization_uses_prefixed_table(): void
    {
        $this->assertSame('crm_organizations', (new Organization)->getTable());
    }

    public function test_creating_an_organization_assigns_external_id_uuid(): void
    {
        $org = Organization::create(['name' => 'Acme Inc']);
        $this->assertTrue(Str::isUuid($org->external_id));
    }

    public function test_organization_uses_soft_deletes(): void
    {
        $org = Organization::create(['name' => 'Bye Inc']);
        $org->delete();

        $this->assertSoftDeleted('crm_organizations', ['id' => $org->id]);
    }
}
