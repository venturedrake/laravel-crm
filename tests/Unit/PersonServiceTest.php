<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\PersonService;

function invokeUpdatePersonPhones(PersonService $service, Person $person, $phones): void
{
    $reflection = new ReflectionMethod($service, 'updatePersonPhones');
    $reflection->setAccessible(true);
    $reflection->invoke($service, $person, $phones);
}

test('updatePersonPhones with null leaves existing phones intact', function () {
    $person = Person::create(['first_name' => 'Phone', 'last_name' => 'Keeper']);
    $phone = $person->phones()->create([
        'external_id' => (string) Str::uuid(),
        'number' => '+1-555-0100',
        'type' => 'work',
        'primary' => 1,
    ]);

    invokeUpdatePersonPhones(app(PersonService::class), $person, null);

    expect($person->fresh()->phones()->pluck('id')->all())->toBe([$phone->id]);
});

test('updatePersonPhones with [] still deletes all phones', function () {
    $person = Person::create(['first_name' => 'Phone', 'last_name' => 'Wiper']);
    $person->phones()->create([
        'external_id' => (string) Str::uuid(),
        'number' => '+1-555-0200',
        'type' => 'work',
        'primary' => 1,
    ]);

    invokeUpdatePersonPhones(app(PersonService::class), $person, []);

    expect($person->fresh()->phones()->count())->toBe(0);
});
