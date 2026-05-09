<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Organization;

test('organization uses prefixed table', function () {
    expect((new Organization)->getTable())->toBe('crm_organizations');
});

test('creating an organization assigns external id uuid', function () {
    $org = Organization::create(['name' => 'Acme Inc']);
    expect(Str::isUuid($org->external_id))->toBeTrue();
});

test('organization uses soft deletes', function () {
    $org = Organization::create(['name' => 'Bye Inc']);
    $org->delete();

    $this->assertSoftDeleted('crm_organizations', ['id' => $org->id]);
});
