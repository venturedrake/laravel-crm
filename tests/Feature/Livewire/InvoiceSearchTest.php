<?php

use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceIndex;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Setting;

/*
 * Typing anything into the search box took the invoice list down whether encryption was
 * on or not: the query asked encryptionEnabled() and InvoiceIndex was missing the trait
 * that defines it. These render for real, because the query is what failed.
 */

beforeEach(function () {
    $this->actingAsUserWithPermissions(['view crm invoices']);
});

function invoiceSearch(string $term): array
{
    return Livewire::test(InvoiceIndex::class)
        ->set('search', $term)
        ->assertOk()
        ->viewData('invoices')
        ->pluck('id')
        ->all();
}

it('finds invoices by contact name', function (bool $encrypted) {
    config()->set('laravel-crm.encrypt_db_fields', $encrypted);

    $person = Person::create(['first_name' => 'Jane', 'last_name' => 'Smith']);
    $organization = Organization::create(['name' => 'Cedar Point']);

    // References that share nothing with the names, or these would match on the reference.
    $byPerson = Invoice::create(['reference' => 'Alpha', 'person_id' => $person->id]);
    $byOrganization = Invoice::create(['reference' => 'Beta', 'organization_id' => $organization->id]);
    Invoice::create(['reference' => 'Gamma']);

    expect(invoiceSearch('Jane'))->toBe([$byPerson->id])
        ->and(invoiceSearch('Smith'))->toBe([$byPerson->id])
        ->and(invoiceSearch('Jane Smith'))->toBe([$byPerson->id])
        ->and(invoiceSearch('Cedar'))->toBe([$byOrganization->id])
        ->and(invoiceSearch('No such contact'))->toBeEmpty();
})->with([
    'encryption off' => false,
    'encryption on' => true,
]);

it('finds invoices by number and reference', function (bool $encrypted) {
    config()->set('laravel-crm.encrypt_db_fields', $encrypted);

    Setting::updateOrCreate(['name' => 'invoice_prefix'], ['value' => 'INV-']);

    $numbered = Invoice::create(['reference' => 'Alpha']);
    $referenced = Invoice::create(['reference' => 'PO-7741']);

    $number = $numbered->fresh()->invoice_id;

    expect($number)->toStartWith('INV-')
        ->and(invoiceSearch($number))->toBe([$numbered->id])
        ->and(invoiceSearch((string) $numbered->fresh()->number))->toBe([$numbered->id])
        ->and(invoiceSearch('PO-7741'))->toBe([$referenced->id])
        ->and(invoiceSearch('7741'))->toBe([$referenced->id]);
})->with([
    'encryption off' => false,
    'encryption on' => true,
]);

// With encryption on, the contact ids used to be the first condition in the group. Anding
// them with the number and the reference would turn "or" into "and" and lose both.
it('matches a contact or a reference, not only both', function (bool $encrypted) {
    config()->set('laravel-crm.encrypt_db_fields', $encrypted);

    $person = Person::create(['first_name' => 'Jane', 'last_name' => 'Smith']);

    $byContact = Invoice::create(['reference' => 'Alpha', 'person_id' => $person->id]);
    $byReference = Invoice::create(['reference' => 'Jane renewal']);
    Invoice::create(['reference' => 'Gamma']);

    expect(invoiceSearch('Jane'))->toEqualCanonicalizing([$byContact->id, $byReference->id]);
})->with([
    'encryption off' => false,
    'encryption on' => true,
]);

// A bound parameter per id runs into MySQL's limit of 65,535 a statement.
it('does not bind the ids of matching contacts', function () {
    config()->set('laravel-crm.encrypt_db_fields', true);

    $person = Person::create(['first_name' => 'Jane', 'last_name' => 'Smith']);
    $invoice = Invoice::create(['reference' => 'Alpha', 'person_id' => $person->id]);

    $bindings = [];
    DB::listen(function ($query) use (&$bindings) {
        if (str_contains($query->sql, '"crm_invoices"."person_id" in')) {
            $bindings[] = $query->bindings;
        }
    });

    expect(invoiceSearch('Jane'))->toBe([$invoice->id])
        ->and($bindings)->not->toBeEmpty()
        ->and(collect($bindings)->flatten()->contains($person->id))->toBeFalse();
});

it('lists every invoice once the search is cleared', function () {
    $first = Invoice::create(['reference' => 'First']);
    $second = Invoice::create(['reference' => 'Second']);

    $board = Livewire::test(InvoiceIndex::class)->set('search', 'No such contact');

    expect($board->viewData('invoices'))->toBeEmpty();

    expect($board->set('search', '')->viewData('invoices')->pluck('id')->all())
        ->toEqualCanonicalizing([$first->id, $second->id]);
});
