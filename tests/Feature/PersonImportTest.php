<?php

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\People\PersonImport;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;

function previewRow(array $overrides = []): array
{
    return array_merge([
        'row' => 2,
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'title' => '',
        'email' => 'jane@example.com',
        'phone' => '5551234',
        'organization_name' => '',
        'description' => '',
        'errors' => [],
    ], $overrides);
}

beforeEach(function () {
    $this->actingAsUser();
});

test('mount detects when a preview is in the session', function () {
    session(['crm_person_import_preview' => [previewRow()]]);

    Livewire::test(PersonImport::class)
        ->assertSet('hasPreview', true);
});

test('mount with no preview defaults to empty state', function () {
    Livewire::test(PersonImport::class)
        ->assertSet('hasPreview', false);
});

test('startImport with empty session emits an error and does not run', function () {
    Livewire::test(PersonImport::class)
        ->call('startImport')
        ->assertSet('processing', false)
        ->assertSet('importedCount', 0);
});

test('startImport processes valid rows and creates people', function () {
    session(['crm_person_import_preview' => [
        previewRow(['first_name' => 'Jane', 'email' => 'jane@example.com']),
        previewRow(['first_name' => 'John', 'email' => 'john@example.com', 'phone' => '5559999']),
    ]]);

    Livewire::test(PersonImport::class)
        ->call('startImport')
        ->call('processNextChunk')
        ->assertSet('imported', true)
        ->assertSet('importedCount', 2)
        ->assertSet('skippedCount', 0);

    expect(Person::count())->toBe(2);
    expect(Person::first()->emails()->count())->toBe(1);
    expect(Person::first()->phones()->count())->toBe(1);
    expect(session('crm_person_import_preview'))->toBeNull();
});

test('rows with errors are skipped', function () {
    session(['crm_person_import_preview' => [
        previewRow(['errors' => ['first name is required']]),
        previewRow(['first_name' => 'Ok']),
    ]]);

    Livewire::test(PersonImport::class)
        ->call('startImport')
        ->call('processNextChunk')
        ->assertSet('importedCount', 1)
        ->assertSet('skippedCount', 1);

    expect(Person::count())->toBe(1);
});

test('organization name auto-creates a new organization and reuses it', function () {
    session(['crm_person_import_preview' => [
        previewRow(['first_name' => 'Jane', 'organization_name' => 'Acme']),
        previewRow(['first_name' => 'John', 'organization_name' => 'Acme']),
        previewRow(['first_name' => 'Sue', 'organization_name' => 'Globex']),
    ]]);

    Livewire::test(PersonImport::class)
        ->call('startImport')
        ->call('processNextChunk')
        ->assertSet('importedCount', 3);

    expect(Organization::count())->toBe(2);
    expect(Person::where('first_name', 'Jane')->first()->organization_id)
        ->toBe(Person::where('first_name', 'John')->first()->organization_id);
});

test('organization name lookup matches existing organizations case-insensitively', function () {
    Organization::create(['name' => 'Acme Inc']);

    session(['crm_person_import_preview' => [
        previewRow(['first_name' => 'Jane', 'organization_name' => 'acme inc']),
    ]]);

    Livewire::test(PersonImport::class)
        ->call('startImport')
        ->call('processNextChunk')
        ->assertSet('importedCount', 1);

    expect(Organization::count())->toBe(1);
    expect(Person::first()->organization_id)->toBe(Organization::first()->id);
});

test('rows without an email or phone do not create empty contact records', function () {
    session(['crm_person_import_preview' => [
        previewRow(['first_name' => 'Jane', 'email' => '', 'phone' => '']),
    ]]);

    Livewire::test(PersonImport::class)
        ->call('startImport')
        ->call('processNextChunk')
        ->assertSet('importedCount', 1);

    $person = Person::first();
    expect($person->emails()->count())->toBe(0);
    expect($person->phones()->count())->toBe(0);
});

test('resetForm clears session and component state', function () {
    session(['crm_person_import_preview' => [previewRow()]]);

    Livewire::test(PersonImport::class)
        ->call('resetForm')
        ->assertSet('hasPreview', false)
        ->assertSet('imported', false)
        ->assertSet('importedCount', 0);

    expect(session('crm_person_import_preview'))->toBeNull();
});

test('pagination next and prev clamp to valid bounds', function () {
    $rows = [];
    for ($i = 0; $i < 75; $i++) {
        $rows[] = previewRow(['row' => $i + 2, 'first_name' => 'P'.$i]);
    }
    session(['crm_person_import_preview' => $rows]);

    Livewire::test(PersonImport::class)
        ->assertSet('page', 1)
        ->call('nextPage')->assertSet('page', 2)
        ->call('nextPage')->assertSet('page', 2)
        ->call('prevPage')->assertSet('page', 1)
        ->call('prevPage')->assertSet('page', 1);
});

