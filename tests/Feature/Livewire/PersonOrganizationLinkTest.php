<?php

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Deals\DealEdit;
use VentureDrake\LaravelCrm\Livewire\Leads\LeadCreate;
use VentureDrake\LaravelCrm\Livewire\Leads\LeadEdit;
use VentureDrake\LaravelCrm\Models\Contact;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Services\PersonService;

/*
 * #94: a person and an organization saved together on a lead, deal, quote,
 * order, invoice or purchase order become related contacts -- one Contact row
 * in each direction, as the Related People / Related Organizations panels
 * create. Existing links are kept and never duplicated.
 */

class LinkLeadCreate extends LeadCreate
{
    public function render()
    {
        return '<div></div>';
    }
}

class LinkLeadEdit extends LeadEdit
{
    public function render()
    {
        return '<div></div>';
    }
}

class LinkDealEdit extends DealEdit
{
    public function render()
    {
        return '<div></div>';
    }
}

/**
 * Contact rows linking $from to $to (one direction only).
 */
function contactLinks($from, $to): int
{
    return Contact::where('contactable_type', $from->getMorphClass())
        ->where('contactable_id', $from->id)
        ->where('entityable_type', $to->getMorphClass())
        ->where('entityable_id', $to->id)
        ->count();
}

function expectLinked(Person $person, Organization $organization): void
{
    expect(contactLinks($person, $organization))->toBe(1)
        ->and(contactLinks($organization, $person))->toBe(1);
}

beforeEach(function () {
    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);
});

test('linkToOrganization links both directions once', function () {
    $this->actingAsUser();
    $person = Person::create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $organization = Organization::create(['name' => 'Analytical Engines']);
    $service = app(PersonService::class);

    $service->linkToOrganization($person, $organization);
    $service->linkToOrganization($person, $organization);

    expectLinked($person, $organization);
    expect(Contact::count())->toBe(2);
});

test('linkToOrganization adds only the missing direction', function () {
    $this->actingAsUser();
    $person = Person::create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $organization = Organization::create(['name' => 'Analytical Engines']);
    $person->contacts()->create([
        'entityable_type' => $organization->getMorphClass(),
        'entityable_id' => $organization->id,
    ]);

    app(PersonService::class)->linkToOrganization($person, $organization);

    expectLinked($person, $organization);
});

test('linkToOrganization does nothing without both a person and an organization', function () {
    $this->actingAsUser();
    $service = app(PersonService::class);

    $service->linkToOrganization(Person::create(['first_name' => 'Ada']), null);
    $service->linkToOrganization(null, Organization::create(['name' => 'Analytical Engines']));
    $service->linkToOrganization(null, null);

    expect(Contact::count())->toBe(0);
});

test('creating a lead with a new person and a new organization links them', function () {
    $this->actingAsUserWithPermissions(['view crm leads', 'create crm leads']);

    Livewire::test(LinkLeadCreate::class)
        ->set('person_name', 'Ada Lovelace')
        ->set('organization_name', 'Analytical Engines')
        ->set('title', 'Engine lead')
        ->call('save')
        ->assertHasNoErrors();

    $lead = Lead::firstWhere('title', 'Engine lead');

    expect($lead->person)->not->toBeNull()
        ->and($lead->organization)->not->toBeNull();

    expectLinked($lead->person, $lead->organization);
});

test('creating a lead with only a person adds no links', function () {
    $this->actingAsUserWithPermissions(['view crm leads', 'create crm leads']);

    Livewire::test(LinkLeadCreate::class)
        ->set('person_name', 'Ada Lovelace')
        ->set('title', 'Engine lead')
        ->call('save')
        ->assertHasNoErrors();

    expect(Contact::count())->toBe(0);
});

test('renaming the person on a lead links the new person and keeps the old link', function () {
    $this->actingAsUserWithPermissions(['view crm leads', 'edit crm leads']);
    $old = Person::create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $organization = Organization::create(['name' => 'Analytical Engines']);
    app(PersonService::class)->linkToOrganization($old, $organization);
    $lead = Lead::create([
        'title' => 'Engine lead',
        'person_id' => $old->id,
        'organization_id' => $organization->id,
        'currency' => 'USD',
    ]);

    // Typing a name that no longer matches drops person_id (scenario 1), so
    // saving creates a new person.
    $component = Livewire::test(LinkLeadEdit::class, ['lead' => $lead])
        ->set('person_name', 'Charles Babbage');

    expect($component->get('person_id'))->toBeNull();

    $component->call('save')->assertHasNoErrors();

    $new = $lead->fresh()->person;

    expect($new->id)->not->toBe($old->id)
        ->and($new->name)->toBe('Charles Babbage');

    expectLinked($new, $organization);
    expectLinked($old, $organization);
});

test('picking an existing person and organization on a deal links them once', function () {
    $this->actingAsUserWithPermissions(['view crm deals', 'edit crm deals']);
    $placeholder = Person::create(['first_name' => 'Placeholder']);
    $person = Person::create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $organization = Organization::create(['name' => 'Analytical Engines']);
    $deal = Deal::create(['title' => 'Engine deal', 'person_id' => $placeholder->id, 'currency' => 'USD']);

    Livewire::test(LinkDealEdit::class, ['deal' => $deal])
        ->call('linkPerson', $person->id)
        ->call('linkOrganization', $organization->id)
        ->call('save')
        ->assertHasNoErrors();

    $deal->refresh();

    expect($deal->person_id)->toBe($person->id)
        ->and($deal->organization_id)->toBe($organization->id);

    expectLinked($person, $organization);

    // Saving again adds no duplicate rows.
    Livewire::test(LinkDealEdit::class, ['deal' => $deal])
        ->call('save')
        ->assertHasNoErrors();

    expectLinked($person, $organization);
    expect(Contact::count())->toBe(2);
});
