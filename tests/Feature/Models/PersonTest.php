<?php

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Person;

test('person uses prefixed people table', function () {
    expect((new Person)->getTable())->toBe('crm_people');
});

test('creating a person assigns external id uuid', function () {
    $person = Person::create(['first_name' => 'Jane', 'last_name' => 'Doe']);
    expect(Str::isUuid($person->external_id))->toBeTrue();
});

test('name attribute concatenates first and last', function () {
    $person = Person::create(['first_name' => 'Jane', 'last_name' => 'Doe']);
    expect($person->name)->toBe('Jane Doe');
});

test('name attribute trims when only first name', function () {
    $person = Person::create(['first_name' => 'Madonna']);
    expect($person->name)->toBe('Madonna');
});

test('person uses soft deletes', function () {
    $person = Person::create(['first_name' => 'Bye']);
    $person->delete();

    $this->assertSoftDeleted('crm_people', ['id' => $person->id]);
});

test('morph relationships defined', function () {
    $person = new Person;

    expect($person->emails())->toBeInstanceOf(MorphMany::class);
    expect($person->phones())->toBeInstanceOf(MorphMany::class);
    expect($person->addresses())->toBeInstanceOf(MorphMany::class);
    expect($person->contacts())->toBeInstanceOf(MorphMany::class);
});
