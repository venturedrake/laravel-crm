<?php

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Organizations\OrganizationImport;
use VentureDrake\LaravelCrm\Models\Organization;

function orgPreviewRow(array $overrides = []): array
{
    return array_merge([
        'row' => 2,
        'name' => 'Acme',
        'email' => 'hello@acme.com',
        'phone' => '5551234',
        'website_url' => 'https://acme.com',
        'vat_number' => '',
        'description' => '',
        'errors' => [],
    ], $overrides);
}

beforeEach(function () {
    $this->actingAsUser();
});

test('mount detects when a preview is in the session', function () {
    session(['crm_organization_import_preview' => [orgPreviewRow()]]);

    Livewire::test(OrganizationImport::class)
        ->assertSet('hasPreview', true);
});

test('mount with no preview defaults to empty state', function () {
    Livewire::test(OrganizationImport::class)
        ->assertSet('hasPreview', false);
});

test('startImport with empty session emits an error and does not run', function () {
    Livewire::test(OrganizationImport::class)
        ->call('startImport')
        ->assertSet('processing', false)
        ->assertSet('importedCount', 0);
});

test('startImport processes valid rows and creates organizations with contact details', function () {
    session(['crm_organization_import_preview' => [
        orgPreviewRow(['name' => 'Acme', 'email' => 'a@acme.com', 'phone' => '111']),
        orgPreviewRow(['name' => 'Globex', 'email' => '', 'phone' => '222']),
    ]]);

    Livewire::test(OrganizationImport::class)
        ->call('startImport')
        ->call('processNextChunk')
        ->assertSet('imported', true)
        ->assertSet('importedCount', 2)
        ->assertSet('skippedCount', 0);

    expect(Organization::count())->toBe(2);
    expect(Organization::where('name', 'Acme')->first()->emails()->count())->toBe(1);
    expect(Organization::where('name', 'Globex')->first()->phones()->count())->toBe(1);
    expect(Organization::where('name', 'Globex')->first()->emails()->count())->toBe(0);
    expect(session('crm_organization_import_preview'))->toBeNull();
});

test('rows with errors are skipped', function () {
    session(['crm_organization_import_preview' => [
        orgPreviewRow(['errors' => ['name is required']]),
        orgPreviewRow(['name' => 'Ok']),
    ]]);

    Livewire::test(OrganizationImport::class)
        ->call('startImport')
        ->call('processNextChunk')
        ->assertSet('importedCount', 1)
        ->assertSet('skippedCount', 1);

    expect(Organization::count())->toBe(1);
});

test('rows that duplicate an existing organization name are skipped', function () {
    Organization::create(['name' => 'Acme Inc']);

    session(['crm_organization_import_preview' => [
        orgPreviewRow(['name' => 'Acme Inc']),
        orgPreviewRow(['name' => 'acme inc']),
        orgPreviewRow(['name' => 'New Co']),
    ]]);

    Livewire::test(OrganizationImport::class)
        ->call('startImport')
        ->call('processNextChunk')
        ->assertSet('importedCount', 1)
        ->assertSet('skippedCount', 2);

    expect(Organization::count())->toBe(2);
});

test('duplicate names within the same file are deduplicated', function () {
    session(['crm_organization_import_preview' => [
        orgPreviewRow(['name' => 'Acme']),
        orgPreviewRow(['name' => 'Acme']),
    ]]);

    Livewire::test(OrganizationImport::class)
        ->call('startImport')
        ->call('processNextChunk')
        ->assertSet('importedCount', 1)
        ->assertSet('skippedCount', 1);

    expect(Organization::count())->toBe(1);
});

test('resetForm clears session and component state', function () {
    session(['crm_organization_import_preview' => [orgPreviewRow()]]);

    Livewire::test(OrganizationImport::class)
        ->call('resetForm')
        ->assertSet('hasPreview', false)
        ->assertSet('imported', false)
        ->assertSet('importedCount', 0);

    expect(session('crm_organization_import_preview'))->toBeNull();
});

test('pagination next and prev clamp to valid bounds', function () {
    $rows = [];
    for ($i = 0; $i < 75; $i++) {
        $rows[] = orgPreviewRow(['row' => $i + 2, 'name' => 'Org '.$i]);
    }
    session(['crm_organization_import_preview' => $rows]);

    Livewire::test(OrganizationImport::class)
        ->assertSet('page', 1)
        ->call('nextPage')->assertSet('page', 2)
        ->call('nextPage')->assertSet('page', 2)
        ->call('prevPage')->assertSet('page', 1)
        ->call('prevPage')->assertSet('page', 1);
});

