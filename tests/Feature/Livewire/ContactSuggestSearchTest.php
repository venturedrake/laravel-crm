<?php

use VentureDrake\LaravelCrm\Livewire\Traits\HasOrganizationSuggest;
use VentureDrake\LaravelCrm\Livewire\Traits\HasPersonSuggest;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;

/*
 * The Organization / Contact person autocomplete on every create and edit form
 * searched with SQL LIKE. With `encrypt_db_fields` on, the columns hold base64
 * ciphertext, so "f" matched almost every row, "Fal" matched none, and results
 * were sorted by ciphertext. Both modes must return the same, correct matches.
 *
 * The suggester uses both traits, as QuoteCreate and friends do, so the
 * SearchesEncryptableContacts trait each of them pulls in is imported twice.
 */
function contactSuggester(): object
{
    return new class
    {
        use HasOrganizationSuggest, HasPersonSuggest;

        public $organization_id;

        public $organization_name;

        public $person_id;

        public $person_name;
    };
}

dataset('encryption', [
    'plaintext' => [false],
    'encrypted' => [true],
]);

beforeEach(function () {
    $this->actingAsUser(['crm_access' => 1]);
});

test('organization suggest matches on the name', function (bool $encrypted) {
    config()->set('laravel-crm.encrypt_db_fields', $encrypted);

    Organization::create(['name' => 'Quest Biotech']);
    Organization::create(['name' => 'PineValley Golf']);
    Organization::create(['name' => 'Falcon Aerospace']);

    $suggester = contactSuggester();

    $suggester->organization_name = 'Fal';
    $suggester->searchOrganizations();

    expect($suggester->organizations->pluck('name')->all())->toBe(['Falcon Aerospace'])
        ->and($suggester->showOrganizations)->toBeTrue();

    $suggester->organization_name = 'f';
    $suggester->searchOrganizations();

    expect($suggester->organizations->pluck('name')->all())->toBe(['Falcon Aerospace', 'PineValley Golf']);
})->with('encryption');

test('person suggest matches on first, last and full name', function (bool $encrypted) {
    config()->set('laravel-crm.encrypt_db_fields', $encrypted);

    Person::create(['first_name' => 'Pamela', 'last_name' => 'Gonzalez']);
    Person::create(['first_name' => 'Carol', 'last_name' => 'Lopez']);
    Person::create(['first_name' => 'Paul', 'last_name' => 'Clark']);

    $suggester = contactSuggester();

    $suggester->person_name = 'Pau';
    $suggester->searchPeople();

    expect($suggester->people->pluck('first_name')->all())->toBe(['Paul'])
        ->and($suggester->showPeople)->toBeTrue();

    $suggester->person_name = 'paul cl';
    $suggester->searchPeople();

    expect($suggester->people->pluck('last_name')->all())->toBe(['Clark']);

    $suggester->person_name = 'pa';
    $suggester->searchPeople();

    expect($suggester->people->pluck('first_name')->all())->toBe(['Pamela', 'Paul']);
})->with('encryption');

test('a term with no matches empties the results and closes the dropdown', function (bool $encrypted) {
    config()->set('laravel-crm.encrypt_db_fields', $encrypted);

    Organization::create(['name' => 'Falcon Aerospace']);
    Person::create(['first_name' => 'Paul', 'last_name' => 'Clark']);

    $suggester = contactSuggester();

    $suggester->organization_name = 'Fal';
    $suggester->searchOrganizations();
    $suggester->person_name = 'Pau';
    $suggester->searchPeople();

    expect($suggester->showOrganizations)->toBeTrue()
        ->and($suggester->showPeople)->toBeTrue();

    $suggester->organization_name = 'zzz';
    $suggester->searchOrganizations();
    $suggester->person_name = 'zzz';
    $suggester->searchPeople();

    expect($suggester->organizations)->toBeEmpty()
        ->and($suggester->showOrganizations)->toBeFalse()
        ->and($suggester->people)->toBeEmpty()
        ->and($suggester->showPeople)->toBeFalse();
})->with('encryption');
