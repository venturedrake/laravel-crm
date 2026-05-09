<?php

use VentureDrake\LaravelCrm\Models\Person;

test('encryption disabled stores plain values', function () {
    config()->set('laravel-crm.encrypt_db_fields', false);

    $person = Person::create(['first_name' => 'Jane', 'last_name' => 'Doe']);
    $row = DB::table('crm_people')->where('id', $person->id)->first();

    expect($row->first_name)->toBe('Jane');
    expect($row->last_name)->toBe('Doe');
});

test('encryption enabled stores encrypted values', function () {
    config()->set('laravel-crm.encrypt_db_fields', true);

    $person = Person::create(['first_name' => 'Jane', 'last_name' => 'Doe']);
    $row = DB::table('crm_people')->where('id', $person->id)->first();

    expect($row->first_name)->not->toBe('Jane');
    expect($row->last_name)->not->toBe('Doe');

    // The model still returns decrypted values via attribute access
    expect($person->fresh()->first_name)->toBe('Jane');
    expect($person->fresh()->last_name)->toBe('Doe');
});
