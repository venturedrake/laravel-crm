<?php

use VentureDrake\LaravelCrm\Models\Email;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Phone;

test('email polymorphic relation to person', function () {
    $person = Person::create(['first_name' => 'Jane']);

    $email = $person->emails()->create(['address' => 'jane@example.com', 'type' => 'work', 'primary' => true]);

    expect($email->address)->toBe('jane@example.com');
    expect($email->primary)->toBeTrue();
    expect($email->emailable->id)->toBe($person->id);
});

test('phone polymorphic relation to person', function () {
    $person = Person::create(['first_name' => 'Bob']);

    $phone = $person->phones()->create(['number' => '+61400000000', 'type' => 'mobile', 'primary' => true]);

    expect($phone->number)->toBe('+61400000000');
    expect($phone->type)->toBe('mobile');
    expect($phone->phoneable->id)->toBe($person->id);
});

test('get primary email returns only primary record', function () {
    $person = Person::create(['first_name' => 'P']);
    $person->emails()->create(['address' => 'a@example.com', 'primary' => false]);
    $primary = $person->emails()->create(['address' => 'b@example.com', 'primary' => true]);

    expect($person->getPrimaryEmail()->id)->toBe($primary->id);
});

test('get primary phone returns only primary record', function () {
    $person = Person::create(['first_name' => 'P']);
    $person->phones()->create(['number' => '111', 'primary' => false]);
    $primary = $person->phones()->create(['number' => '222', 'primary' => true]);

    expect($person->getPrimaryPhone()->id)->toBe($primary->id);
});

test('email uses prefixed table', function () {
    expect((new Email)->getTable())->toBe('crm_emails');
});

test('phone uses prefixed table', function () {
    expect((new Phone)->getTable())->toBe('crm_phones');
});
