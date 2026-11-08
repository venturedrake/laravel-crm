<?php

/*
 * #102: deleting a person or organization warns about the records still
 * linked to it, and those records keep the reference.
 *
 * A person linked to a deal could be deleted with no warning, and the deal
 * then silently lost its contact. Deletion still goes ahead, but the delete
 * dialog now lists what is linked (deleteWarning), and the linked records'
 * show pages show "Name (deleted)" through personWithTrashed() /
 * organizationWithTrashed() rather than an empty field. person() and
 * organization() are unchanged, so the edit forms do not refill a deleted
 * contact.
 */

use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Deals\DealEdit;
use VentureDrake\LaravelCrm\Livewire\Deals\DealShow;
use VentureDrake\LaravelCrm\Livewire\Organizations\OrganizationIndex;
use VentureDrake\LaravelCrm\Livewire\Organizations\OrganizationShow;
use VentureDrake\LaravelCrm\Livewire\People\PersonShow;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Setting;

class WarningDealEdit extends DealEdit
{
    public function render()
    {
        return '<div></div>';
    }
}

beforeEach(function () {
    $this->actingAsUserWithPermissions([
        'view crm people', 'edit crm people', 'delete crm people',
        'view crm organizations', 'edit crm organizations', 'delete crm organizations',
        'view crm deals', 'edit crm deals',
    ]);

    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);
    app('laravel-crm.settings')->forgetCache();
});

function warningTestPerson(): Person
{
    return Person::create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
}

function warningTestText(string $model, string $records): string
{
    return ucfirst(__('laravel-crm::lang.delete_linked_records_warning', [
        'model' => __('laravel-crm::lang.'.$model),
        'records' => $records,
    ]));
}

test('a person linked to deals and a quote is warned about each', function () {
    $person = warningTestPerson();
    Deal::create(['title' => 'Fit-out', 'person_id' => $person->id]);
    Deal::create(['title' => 'Repaint', 'person_id' => $person->id]);
    Quote::create(['title' => 'Fit-out', 'currency' => 'USD', 'person_id' => $person->id]);

    expect($person->linkedRecordCounts())->toBe(['deals' => 2, 'quotes' => 1])
        ->and($person->deleteWarning())->toBe(warningTestText('person', '2 deals and 1 quote'))
        ->and($person->deleteWarning())->toStartWith('This person is linked to 2 deals and 1 quote.');
});

test('an organization counts its people as well as its documents', function () {
    $organization = Organization::create(['name' => 'Analytical Engines']);
    Person::create(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'organization_id' => $organization->id]);
    Person::create(['first_name' => 'Charles', 'last_name' => 'Babbage', 'organization_id' => $organization->id]);
    Deal::create(['title' => 'Difference engine', 'organization_id' => $organization->id]);
    Quote::create(['title' => 'Engine', 'currency' => 'USD', 'organization_id' => $organization->id]);

    expect($organization->deleteWarning())
        ->toBe(warningTestText('organization', '2 people, 1 deal and 1 quote'));
});

test('nothing linked means no warning', function () {
    expect(warningTestPerson()->deleteWarning())->toBeNull()
        ->and(Organization::create(['name' => 'Empty Co'])->deleteWarning())->toBeNull();
});

test('soft-deleted linked records are ignored', function () {
    $person = warningTestPerson();
    Deal::create(['title' => 'Gone', 'person_id' => $person->id])->delete();

    expect($person->linkedRecordCounts())->toBe([])
        ->and($person->deleteWarning())->toBeNull();
});

test('the person and organization show pages put the warning in the delete dialog', function () {
    $person = warningTestPerson();
    $organization = Organization::create(['name' => 'Analytical Engines']);
    Deal::create(['title' => 'Fit-out', 'person_id' => $person->id, 'organization_id' => $organization->id]);

    expect(Livewire::test(PersonShow::class, ['person' => $person])->html())
        ->toContain(e(warningTestText('person', '1 deal')));

    expect(Livewire::test(OrganizationShow::class, ['organization' => $organization])->html())
        ->toContain(e(warningTestText('organization', '1 deal')));
});

test('a deal whose person was deleted shows the name as deleted, without a link', function () {
    $person = warningTestPerson();
    $organization = Organization::create(['name' => 'Analytical Engines']);
    $deal = Deal::create(['title' => 'Fit-out', 'person_id' => $person->id, 'organization_id' => $organization->id]);

    $person->delete();
    $organization->delete();

    $html = Livewire::test(DealShow::class, ['deal' => $deal->fresh()])->html();

    expect($html)
        ->toContain('Ada Lovelace')
        ->toContain('Analytical Engines')
        ->toContain(__('laravel-crm::lang.deleted'))
        ->not->toContain(route('laravel-crm.people.show', $person))
        ->not->toContain(route('laravel-crm.organizations.show', $organization));
});

test('a deal whose person is live still links to them', function () {
    $person = warningTestPerson();
    $deal = Deal::create(['title' => 'Fit-out', 'person_id' => $person->id]);

    expect(Livewire::test(DealShow::class, ['deal' => $deal->fresh()])->html())
        ->toContain(route('laravel-crm.people.show', $person));
});

test('the deal edit form loads with an empty contact once the person is deleted', function () {
    $person = warningTestPerson();
    $deal = Deal::create(['title' => 'Fit-out', 'person_id' => $person->id]);

    $person->delete();

    Livewire::test(WarningDealEdit::class, ['deal' => $deal->fresh()])
        ->assertOk()
        ->assertSet('person_id', null)
        ->assertSet('person_name', null);
});

/**
 * Seed `$count` organizations, each with a person and a deal, so every row
 * has a warning to show.
 */
function seedOrganizationsForWarning(int $count): void
{
    for ($i = 0; $i < $count; $i++) {
        $organization = Organization::create(['name' => "Org {$i}"]);
        Person::create(['first_name' => 'Person', 'last_name' => (string) $i, 'organization_id' => $organization->id]);
        Deal::create(['title' => "Deal {$i}", 'organization_id' => $organization->id]);
    }
}

function organizationIndexWarningQueryCount(): int
{
    $count = 0;

    DB::listen(function () use (&$count) {
        $count++;
    });

    Livewire::test(OrganizationIndex::class)->assertOk();

    return $count;
}

test('the organization index shows each warning without a query per row', function () {
    seedOrganizationsForWarning(5);

    // A throwaway render first: SettingService memoises its settings for the
    // life of the container, so the first render is one query dearer.
    organizationIndexWarningQueryCount();

    $fiveRows = organizationIndexWarningQueryCount();

    seedOrganizationsForWarning(20);
    $twentyFiveRows = organizationIndexWarningQueryCount();

    expect($twentyFiveRows)->toBe($fiveRows);

    expect(Livewire::test(OrganizationIndex::class)->html())
        ->toContain(e(warningTestText('organization', '1 person and 1 deal')));
});
