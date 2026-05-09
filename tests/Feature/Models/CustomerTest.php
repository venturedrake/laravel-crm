<?php

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Customer;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;

test('customer uses prefixed table name', function () {
    expect((new Customer)->getTable())->toBe('crm_customers');
});

test('creating a customer assigns uuid', function () {
    $customer = Customer::create(['name' => 'Acme']);
    expect(Str::isUuid($customer->external_id))->toBeTrue();
});

test('customer morphs to a person', function () {
    $person = Person::create(['first_name' => 'Bob']);
    $customer = Customer::create([
        'name' => 'Bob', 'customerable_type' => Person::class, 'customerable_id' => $person->id,
    ]);

    expect($customer->customerable())->toBeInstanceOf(MorphTo::class);
    expect($customer->customerable->is($person))->toBeTrue();
});

test('customer morphs to an organization', function () {
    $org = Organization::create(['name' => 'Acme']);
    $customer = Customer::create([
        'name' => 'Acme', 'customerable_type' => Organization::class, 'customerable_id' => $org->id,
    ]);

    expect($customer->customerable->is($org))->toBeTrue();
});

test('customer name is encrypted when enabled', function () {
    config()->set('laravel-crm.encrypt_db_fields', true);

    $customer = Customer::create(['name' => 'Encrypted Customer']);
    $raw = DB::table('crm_customers')->where('id', $customer->id)->value('name');

    expect($raw)->not->toBe('Encrypted Customer');
    expect($customer->fresh()->name)->toBe('Encrypted Customer');
});

test('customer uses soft deletes', function () {
    $customer = Customer::create(['name' => 'Bin']);
    $customer->delete();
    $this->assertSoftDeleted('crm_customers', ['id' => $customer->id]);
});
