<?php

use Carbon\Carbon;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceEdit;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceIndex;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Setting;

/*
 * Regression cover for the null-due-date fatal on the invoices index:
 *
 *   Error: Call to a member function diffinDays() on null
 *   … VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceIndex
 *
 * `crm_invoices.due_date` is nullable and the V2 API accepts a null
 * `due_date`, so a single dateless invoice used to take down the whole
 * index for every user. Unlike the authorization suite, these tests
 * deliberately do NOT stub render() — the entire point is that the real
 * blade renders against a record with null dates.
 */

beforeEach(function () {
    // The index row actions mount `crm-invoice-send`, whose subject blade reads
    // Setting::where('name', 'organization_name')->first()->value unguarded.
    // Seed it so an unrelated missing setting can't masquerade as a date crash.
    Setting::updateOrCreate(['name' => 'organization_name'], ['value' => 'Test Organization']);

    $this->actingAsUserWithPermissions([
        'view crm invoices',
        'create crm invoices',
        'edit crm invoices',
        'delete crm invoices',
    ]);
});

it('renders the invoice index when an invoice has no issue or due date', function () {
    $invoice = Invoice::create([
        'reference' => 'Null dated invoice',
        'issue_date' => null,
        'due_date' => null,
    ]);

    expect($invoice->due_date)->toBeNull();
    expect($invoice->issue_date)->toBeNull();

    Livewire::test(InvoiceIndex::class)
        ->assertOk()
        ->assertSee('Null dated invoice');
});

it('still renders the overdue-by cell for a dated invoice alongside a dateless one', function () {
    Invoice::create([
        'reference' => 'Null dated invoice',
        'issue_date' => null,
        'due_date' => null,
    ]);

    $overdue = Invoice::create([
        'reference' => 'Overdue invoice',
        'issue_date' => Carbon::now()->subDays(40),
        'due_date' => Carbon::now()->subDays(10),
    ]);

    // The guard added to the `cell_overdue_by` scope must skip the null
    // row without suppressing the real overdue text on its neighbour.
    Livewire::test(InvoiceIndex::class)
        ->assertOk()
        ->assertSee('Null dated invoice')
        ->assertSee('Overdue invoice')
        ->assertSee($overdue->due_date->diffForHumans());
});

it('mounts the invoice edit form with null dates instead of fatalling', function () {
    // Locks the `?? null` precedence bug: `$invoice->due_date->format(...) ?? null`
    // evaluated the method call first and fatalled on null before `??` could fire.
    $invoice = Invoice::create([
        'reference' => 'Null dated invoice',
        'issue_date' => null,
        'due_date' => null,
    ]);

    Livewire::test(InvoiceEdit::class, ['invoice' => $invoice])
        ->assertOk()
        ->assertSet('issue_date', null)
        ->assertSet('due_date', null);
});

it('guards every due-date badge branch on the invoice show blade', function () {
    // The show blade pulls in activity/timeline tables the minimal TestSchema
    // does not ship, so assert the guard through the blade source rather than
    // adding half the CRM to the test schema. Every branch that calls a Carbon
    // method on due_date must be preceded by a `$invoice->due_date &&` guard.
    $blade = file_get_contents(
        __DIR__.'/../../../resources/views/livewire/invoices/invoice-show.blade.php'
    );

    // Only inspect the @if/@elseif conditions — the badge bodies legitimately
    // dereference due_date, having already passed the guard.
    preg_match_all('/@(?:if|elseif)\((.*)\)\R/', $blade, $matches);

    $dueDateConditions = array_values(array_filter(
        $matches[1],
        fn (string $condition) => str_contains($condition, '$invoice->due_date->'),
    ));

    expect($dueDateConditions)->not->toBeEmpty();

    foreach ($dueDateConditions as $condition) {
        expect($condition)->toContain('$invoice->due_date &&');
    }
});
