<?php

use function VentureDrake\LaravelCrm\Http\Helpers\PersonName\personName;

test('full name returns first space last', function () {
    $person = new stdClass;
    $person->first_name = 'Jane';
    $person->middle_name = null;
    $person->last_name = 'Doe';

    expect(personName($person))->toBe('Jane Doe');
});

test('full name includes middle name when set', function () {
    $person = new stdClass;
    $person->first_name = 'John';
    $person->middle_name = 'Paul';
    $person->last_name = 'Smith';

    expect(personName($person))->toBe('John Paul Smith');
});

test('only first name returns first name', function () {
    $person = new stdClass;
    $person->first_name = 'Madonna';
    $person->middle_name = null;
    $person->last_name = null;

    expect(personName($person))->toBe('Madonna');
});

test('only last name returns last name', function () {
    $person = new stdClass;
    $person->first_name = null;
    $person->middle_name = null;
    $person->last_name = 'Cher';

    expect(personName($person))->toBe('Cher');
});

test('null object returns empty string', function () {
    expect(personName(null))->toBe('');
});

test('all empty fields returns empty string', function () {
    $person = new stdClass;
    $person->first_name = null;
    $person->middle_name = null;
    $person->last_name = null;

    expect(trim(personName($person)))->toBe('');
});
