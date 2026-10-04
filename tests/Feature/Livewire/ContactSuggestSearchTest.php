<?php

use Livewire\Component;
use Livewire\Livewire;
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

/**
 * A form host as Livewire sees it. Like LeadCreate and friends it defines its
 * own updatedPersonName() / updatedOrganizationName(), which must not stop the
 * traits' unlink hooks from firing.
 */
class ContactSuggestHost extends Component
{
    use HasOrganizationSuggest, HasPersonSuggest;

    public $organization_id;

    public $organization_name;

    public $person_id;

    public $person_name;

    public $updatedNames = [];

    public function updatedPersonName($value)
    {
        $this->updatedNames[] = $value;
    }

    public function updatedOrganizationName($value)
    {
        $this->updatedNames[] = $value;
    }

    public function render()
    {
        return '<div></div>';
    }
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

/*
 * Picking a suggestion links the record. Editing the name afterwards ("Paul
 * Clark" → "Paul") used to keep the link, so the "New" badge stayed hidden and
 * saving attached Paul Clark instead of creating "Paul".
 */
test('editing a linked person name away from the match unlinks the person', function (bool $encrypted) {
    config()->set('laravel-crm.encrypt_db_fields', $encrypted);

    $person = Person::create(['first_name' => 'Paul', 'last_name' => 'Clark']);

    $suggester = contactSuggester();
    $suggester->linkPerson($person->id);

    expect($suggester->person_id)->toBe($person->id)
        ->and($suggester->person_name)->toBe('Paul Clark');

    $suggester->person_name = 'Paul';
    $suggester->updatedHasPersonSuggest('person_name', 'Paul');

    expect($suggester->person_id)->toBeNull()
        ->and($suggester->person_name)->toBe('Paul');
})->with('encryption');

test('a person name that still matches the linked person keeps the link', function (bool $encrypted) {
    config()->set('laravel-crm.encrypt_db_fields', $encrypted);

    $person = Person::create(['first_name' => 'Paul', 'last_name' => 'Clark']);

    $suggester = contactSuggester();
    $suggester->linkPerson($person->id);
    $suggester->updatedHasPersonSuggest('person_name', 'Paul Clark');

    expect($suggester->person_id)->toBe($person->id);
})->with('encryption');

test('editing a linked organization name away from the match unlinks the organization', function (bool $encrypted) {
    config()->set('laravel-crm.encrypt_db_fields', $encrypted);

    $organization = Organization::create(['name' => 'Falcon Aerospace']);

    $suggester = contactSuggester();
    $suggester->linkOrganization($organization->id);

    expect($suggester->organization_id)->toBe($organization->id)
        ->and($suggester->organization_name)->toBe('Falcon Aerospace');

    $suggester->organization_name = 'Falcon';
    $suggester->updatedHasOrganizationSuggest('organization_name', 'Falcon');

    expect($suggester->organization_id)->toBeNull()
        ->and($suggester->organization_name)->toBe('Falcon');
})->with('encryption');

test('an organization name that still matches the linked organization keeps the link', function (bool $encrypted) {
    config()->set('laravel-crm.encrypt_db_fields', $encrypted);

    $organization = Organization::create(['name' => 'Falcon Aerospace']);

    $suggester = contactSuggester();
    $suggester->linkOrganization($organization->id);
    $suggester->updatedHasOrganizationSuggest('organization_name', 'Falcon Aerospace');

    expect($suggester->organization_id)->toBe($organization->id);
})->with('encryption');

test('livewire fires the unlink hooks when the name is edited on a form', function () {
    $person = Person::create(['first_name' => 'Paul', 'last_name' => 'Clark']);
    $organization = Organization::create(['name' => 'Falcon Aerospace']);

    Livewire::test(ContactSuggestHost::class)
        ->call('linkPerson', $person->id)
        ->call('linkOrganization', $organization->id)
        ->assertSet('person_id', $person->id)
        ->assertSet('organization_id', $organization->id)
        ->set('person_name', 'Paul Clark')
        ->set('organization_name', 'Falcon Aerospace')
        ->assertSet('person_id', $person->id)
        ->assertSet('organization_id', $organization->id)
        ->set('person_name', 'Paul')
        ->set('organization_name', 'Falcon')
        ->assertSet('person_id', null)
        ->assertSet('organization_id', null)
        ->assertSet('updatedNames', ['Paul Clark', 'Falcon Aerospace', 'Paul', 'Falcon']);
});
