<?php

/*
 * The Guests picker on calls, meetings and lunches, and what a record page loads.
 *
 * Each *Related panel and every activity row ran Person::get() in render() to
 * fill the picker, so a quote page hydrated about 2,450 models and an invoice
 * with a long timeline about 20,000, each decrypting a name. The picker now asks
 * the server as the user types, and the panels load when their tab is opened.
 */

use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\ActivityMenu;
use VentureDrake\LaravelCrm\Livewire\ActivityTabs;
use VentureDrake\LaravelCrm\Livewire\Calls\CallItem;
use VentureDrake\LaravelCrm\Livewire\Calls\CallRelated;
use VentureDrake\LaravelCrm\Livewire\Lunches\LunchItem;
use VentureDrake\LaravelCrm\Livewire\Lunches\LunchRelated;
use VentureDrake\LaravelCrm\Livewire\Meetings\MeetingItem;
use VentureDrake\LaravelCrm\Livewire\Meetings\MeetingRelated;
use VentureDrake\LaravelCrm\Models\Call;
use VentureDrake\LaravelCrm\Models\Lunch;
use VentureDrake\LaravelCrm\Models\Meeting;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Setting;

dataset('pickers', [
    'call' => [CallRelated::class, CallItem::class, Call::class, 'call', 'calls'],
    'meeting' => [MeetingRelated::class, MeetingItem::class, Meeting::class, 'meeting', 'meetings'],
    'lunch' => [LunchRelated::class, LunchItem::class, Lunch::class, 'lunch', 'lunches'],
]);

dataset('encryption', ['plain' => [false], 'encrypted' => [true]]);

beforeEach(function () {
    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);
    Setting::updateOrCreate(['name' => 'team'], ['value' => 'related']);
});

function activityOn(Person $parent, string $model, string $morph): Call|Meeting|Lunch
{
    return $model::create([
        'name' => 'Catch up',
        'start_at' => now()->toDateTimeString(),
        'finish_at' => now()->addHour()->toDateTimeString(),
        $morph.'_type' => $parent->getMorphClass(),
        $morph.'_id' => $parent->id,
    ]);
}

function seedPeople(int $count, string $firstName = 'Bulk'): void
{
    foreach (range(1, $count) as $i) {
        Person::create(['first_name' => $firstName, 'last_name' => 'Person'.$i]);
    }
}

function countPersonModels(callable $callback): int
{
    $count = 0;

    Event::listen('eloquent.retrieved: '.Person::class, function () use (&$count) {
        $count++;
    });

    $callback();

    return $count;
}

it('returns at most the limit of matching people for a search', function (string $related, string $item, string $model, string $morph, string $relation, bool $encrypted) {
    config()->set('laravel-crm.encrypt_db_fields', $encrypted);
    $this->actingAsUserWithPermissions(["create crm {$relation}"]);

    seedPeople(CallRelated::GUEST_SEARCH_LIMIT + 5, 'Rosalind');
    Person::create(['first_name' => 'Someone', 'last_name' => 'Else']);

    $options = Livewire::test($related, ['model' => Organization::create(['name' => 'Parent'])])
        ->call('searchGuests', 'rosal')
        ->get('guestOptions');

    expect($options)->toHaveCount(CallRelated::GUEST_SEARCH_LIMIT);
    expect(collect($options)->pluck('name')->every(fn ($name) => str_starts_with($name, 'Rosalind')))->toBeTrue();
})->with('pickers')->with('encryption');

it('matches a first and last name together', function (bool $encrypted) {
    config()->set('laravel-crm.encrypt_db_fields', $encrypted);
    $this->actingAsUserWithPermissions(['create crm calls']);

    $person = Person::create(['first_name' => 'Rosalind', 'last_name' => 'Franklin']);
    Person::create(['first_name' => 'Rosalind', 'last_name' => 'Other']);

    Livewire::test(CallRelated::class, ['model' => Organization::create(['name' => 'Parent'])])
        ->call('searchGuests', 'rosalind fran')
        ->assertSet('guestOptions', [['id' => $person->id, 'name' => 'Rosalind Franklin']]);
})->with('encryption');

it('returns nobody until something is typed', function (string $related, string $item, string $model, string $morph, string $relation) {
    $this->actingAsUserWithPermissions(["create crm {$relation}"]);
    seedPeople(3);

    Livewire::test($related, ['model' => Organization::create(['name' => 'Parent'])])
        ->assertSet('guestOptions', [])
        ->call('searchGuests', '')
        ->assertSet('guestOptions', [])
        ->call('searchGuests', '   ')
        ->assertSet('guestOptions', []);
})->with('pickers');

it('does not load people to render a panel or an activity row', function (string $related, string $item, string $model, string $morph, string $relation) {
    $this->actingAsUserWithPermissions(["create crm {$relation}", "edit crm {$relation}"]);
    $parent = Person::create(['first_name' => 'Parent']);
    $record = activityOn($parent, $model, $morph);
    seedPeople(10);

    expect(countPersonModels(function () use ($related, $item, $parent, $morph, $record) {
        Livewire::test($item, [$morph => $record]);
        Livewire::test($related, ['model' => $parent]);
    }))->toBeLessThan(5);
})->with('pickers');

it('shows existing guests when editing and keeps them through a search', function (string $related, string $item, string $model, string $morph, string $relation, bool $encrypted) {
    config()->set('laravel-crm.encrypt_db_fields', $encrypted);
    $this->actingAsUserWithPermissions(["edit crm {$relation}"]);

    $parent = Person::create(['first_name' => 'Parent']);
    $record = activityOn($parent, $model, $morph);
    $guest = Person::create(['first_name' => 'Attached', 'last_name' => 'Guest']);
    $record->contacts()->create(['entityable_type' => $guest->getMorphClass(), 'entityable_id' => $guest->id]);
    $found = Person::create(['first_name' => 'Rosalind', 'last_name' => 'Franklin']);

    $test = Livewire::test($item, [$morph => $record])
        ->assertSet('guests', [$guest->id])
        ->assertSet('guestOptions', [])
        ->call('edit')
        ->assertSet('guestOptions', [['id' => $guest->id, 'name' => 'Attached Guest']])
        ->assertSee('Attached Guest');

    $test->call('searchGuests', 'rosal');

    expect($test->get('guestOptions'))->toBe([
        ['id' => $guest->id, 'name' => 'Attached Guest'],
        ['id' => $found->id, 'name' => 'Rosalind Franklin'],
    ]);
})->with('pickers')->with('encryption');

it('attaches a guest picked from a search', function (string $related, string $item, string $model, string $morph, string $relation) {
    $this->actingAsUserWithPermissions(["create crm {$relation}"]);

    $parent = Organization::create(['name' => 'Parent']);
    $guest = Person::create(['first_name' => 'Rosalind', 'last_name' => 'Franklin']);

    Livewire::test($related, ['model' => $parent])
        ->call('searchGuests', 'rosal')
        ->set('guests', [$guest->id])
        ->set('name', 'Catch up')
        ->set('start_at', now()->toDateTimeString())
        ->set('finish_at', now()->addHour()->toDateTimeString())
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('guests', [])
        ->assertSet('guestOptions', []);

    // A call also links the team members on it, so look at the people only.
    expect($parent->{$relation}()->first()->contacts()->where('entityable_type', $guest->getMorphClass())->pluck('entityable_id')->all())->toBe([$guest->id]);
})->with('pickers');

it('refuses a search from someone who could not open the form', function (string $related, string $item, string $model, string $morph, string $relation) {
    $this->actingAsUserWithPermissions(["view crm {$relation}"]);
    $parent = Person::create(['first_name' => 'Parent']);
    $record = activityOn($parent, $model, $morph);

    Livewire::test($related, ['model' => $parent])
        ->call('searchGuests', 'par')
        ->assertForbidden();

    Livewire::test($item, [$morph => $record])
        ->call('searchGuests', 'par')
        ->assertForbidden();
})->with('pickers');

describe('a record page', function () {
    beforeEach(function () {
        $this->actingAsUserWithPermissions([
            'view crm quotes', 'view crm calls', 'create crm calls', 'edit crm calls',
            'view crm meetings', 'create crm meetings', 'edit crm meetings',
            'view crm lunches', 'create crm lunches', 'edit crm lunches',
        ]);
    });

    it('loads no people beyond the record\'s own contacts', function () {
        seedPeople(50);

        $contact = Person::create(['first_name' => 'Quote', 'last_name' => 'Contact']);
        $quote = Quote::create(['title' => 'Sample', 'currency' => 'USD', 'person_id' => $contact->id]);

        $call = $quote->calls()->create([
            'name' => 'Catch up',
            'start_at' => now()->toDateTimeString(),
            'finish_at' => now()->addHour()->toDateTimeString(),
        ]);
        $call->contacts()->create(['entityable_type' => $contact->getMorphClass(), 'entityable_id' => $contact->id]);
        $quote->activities()->create([
            'timelineable_type' => $quote->getMorphClass(),
            'timelineable_id' => $quote->id,
            'recordable_type' => $call->getMorphClass(),
            'recordable_id' => $call->id,
        ]);

        $people = countPersonModels(function () use ($quote) {
            Livewire::test(ActivityTabs::class, ['model' => $quote])->assertOk();
        });

        expect($people)->toBeLessThan(5);
    });

    it('leaves the tabbed panels unmounted until their tab is opened', function () {
        $quote = Quote::create(['title' => 'Sample', 'currency' => 'USD']);

        Livewire::test(ActivityTabs::class, ['model' => $quote])
            ->assertDontSee(ucfirst(__('laravel-crm::lang.add_call')))
            ->assertDontSee(ucfirst(__('laravel-crm::lang.add_task')));
    });

    it('still opens a panel from the action buttons', function () {
        Livewire::test(ActivityMenu::class)
            ->call('addCall')
            ->assertDispatched('select-activity-tab');

        Livewire::test(ActivityTabs::class, ['model' => Quote::create(['title' => 'Sample', 'currency' => 'USD'])])
            ->dispatch('select-activity-tab', 'calls')
            ->assertSet('activeTab', 'calls');
    });
});
