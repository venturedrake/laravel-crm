<?php

/*
 * Who was on a call: the Guests field starts with the person the call is
 * about, and a Team members field records the CRM users on it, starting with
 * the user logging the call.
 */

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Calls\CallItem;
use VentureDrake\LaravelCrm\Livewire\Calls\CallRelated;
use VentureDrake\LaravelCrm\Models\Call;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

beforeEach(function () {
    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);
    Setting::updateOrCreate(['name' => 'team'], ['value' => 'related']);

    $this->user = $this->actingAsUserWithPermissions(['create crm calls', 'view crm calls', 'edit crm calls']);
    $this->colleague = User::create(['name' => 'Colleague', 'email' => 'colleague@crm.test', 'password' => bcrypt('secret'), 'crm_access' => 1]);
    $this->sam = Person::create(['first_name' => 'Sam', 'last_name' => 'Lee']);
});

function saveCall($component)
{
    return $component
        ->set('name', 'Catch up')
        ->set('start_at', now()->toDateTimeString())
        ->set('finish_at', now()->addHour()->toDateTimeString())
        ->call('save')
        ->assertHasNoErrors();
}

it('starts a call on a person page with that person as a guest and me as the team member', function () {
    $component = Livewire::test(CallRelated::class, ['model' => $this->sam])
        ->assertSet('guests', [$this->sam->id])
        ->assertSet('team_members', [$this->user->id]);

    // The picker has to hold an option for the guest it shows as selected.
    expect(collect($component->get('guestOptions'))->pluck('name')->all())->toBe(['Sam Lee']);
});

it('starts a call on a record with its linked person as a guest', function () {
    $deal = Deal::create(['title' => 'New site', 'person_id' => $this->sam->id]);

    Livewire::test(CallRelated::class, ['model' => $deal])->assertSet('guests', [$this->sam->id]);
});

it('starts a call on an organisation with no guest', function () {
    Livewire::test(CallRelated::class, ['model' => Organization::create(['name' => 'JumpSkipAreo'])])
        ->assertSet('guests', [])
        ->assertSet('guestOptions', [])
        ->assertSet('team_members', [$this->user->id]);
});

it('saves the prefilled guest and team member, and prefills again for the next call', function () {
    saveCall(Livewire::test(CallRelated::class, ['model' => $this->sam]))
        ->assertSet('guests', [$this->sam->id])
        ->assertSet('team_members', [$this->user->id]);

    $call = Call::sole();

    expect($call->guests()->pluck('entityable_id')->all())->toBe([$this->sam->id])
        ->and($call->teamMembers()->pluck('entityable_id')->all())->toBe([$this->user->id])
        ->and($call->contacts()->count())->toBe(2);
});

it('logs a call for a colleague by swapping the team member', function () {
    saveCall(Livewire::test(CallRelated::class, ['model' => $this->sam])->set('team_members', [$this->colleague->id]));

    expect(Call::sole()->teamMembers()->pluck('entityable_id')->all())->toBe([$this->colleague->id]);
});

it('lets the prefilled guest be removed', function () {
    saveCall(Livewire::test(CallRelated::class, ['model' => $this->sam])->set('guests', []));

    expect(Call::sole()->guests()->count())->toBe(0);
});

it('edits guests and team members independently', function () {
    $pat = Person::create(['first_name' => 'Pat']);
    saveCall(Livewire::test(CallRelated::class, ['model' => $this->sam]));
    $call = Call::sole();

    Livewire::test(CallItem::class, ['call' => $call])
        ->assertSet('guests', [$this->sam->id])
        ->assertSet('team_members', [$this->user->id])
        ->call('edit')
        ->set('team_members', [$this->user->id, $this->colleague->id])
        ->call('update')
        ->assertHasNoErrors();

    expect($call->guests()->pluck('entityable_id')->all())->toBe([$this->sam->id])
        ->and($call->teamMembers()->pluck('entityable_id')->sort()->values()->all())->toBe([$this->user->id, $this->colleague->id]);

    Livewire::test(CallItem::class, ['call' => $call])
        ->call('edit')
        ->set('guests', [$pat->id])
        ->call('update')
        ->assertHasNoErrors();

    expect($call->guests()->pluck('entityable_id')->all())->toBe([$pat->id])
        ->and($call->teamMembers()->count())->toBe(2);
});

it('keeps a guest and a team member that share an id apart', function () {
    // Person 1 and user 1 both exist; removing the guest must not remove the user.
    expect($this->sam->id)->toBe($this->user->id);

    saveCall(Livewire::test(CallRelated::class, ['model' => $this->sam]));
    $call = Call::sole();

    Livewire::test(CallItem::class, ['call' => $call])
        ->call('edit')
        ->set('guests', [])
        ->call('update');

    expect($call->guests()->count())->toBe(0)
        ->and($call->teamMembers()->pluck('entityable_id')->all())->toBe([$this->user->id]);
});

it('names guests and team members in the call summary and lists them in its details', function () {
    saveCall(Livewire::test(CallRelated::class, ['model' => $this->sam])->set('team_members', [$this->colleague->id]));

    // The summary names both without the call being opened…
    Livewire::test(CallItem::class, ['call' => Call::sole()])
        ->assertSet('expanded', false)
        ->assertSee('Sam Lee')
        ->assertSee('Colleague')
        ->assertDontSee('Team members')
        // …and the details list them under their headings.
        ->set('expanded', true)
        ->assertSee('Team members');
});

it('shows a call made before team members existed as it was', function () {
    $call = $this->sam->calls()->create(['name' => 'Old call', 'start_at' => now(), 'finish_at' => now()->addHour()]);
    $call->contacts()->create(['entityable_type' => $this->sam->getMorphClass(), 'entityable_id' => $this->sam->id]);

    Livewire::test(CallItem::class, ['call' => $call])
        ->assertSet('guests', [$this->sam->id])
        ->assertSet('team_members', [])
        ->assertSee('Sam Lee')
        ->assertDontSee('Team members');
});

it('shows the start time and a duration on the call summary', function () {
    $start = now()->setTime(9, 30);

    $short = $this->sam->calls()->create(['name' => 'Short call', 'start_at' => $start, 'finish_at' => $start->copy()->addMinutes(45)]);
    $long = $this->sam->calls()->create(['name' => 'Long call', 'start_at' => $start, 'finish_at' => $start->copy()->addMinutes(135)]);
    $hour = $this->sam->calls()->create(['name' => 'Hour call', 'start_at' => $start, 'finish_at' => $start->copy()->addMinutes(60)]);

    expect($short->durationLabel())->toBe('45 minutes')
        ->and($long->durationLabel())->toBe('2 hours 15 minutes')
        ->and($hour->durationLabel())->toBe('1 hour');

    Livewire::test(CallItem::class, ['call' => $long])
        ->assertSee('09:30 AM on')
        ->assertSee('Duration 2 hours 15 minutes')
        ->assertDontSee('Start at')
        ->assertDontSee('Finish at');
});

it('logs a call as called or received and shows it on the summary', function () {
    Livewire::test(CallRelated::class, ['model' => $this->sam])
        ->assertSet('direction', Call::OUTBOUND)
        ->set('name', 'They rang')
        ->set('start_at', now()->toDateTimeString())
        ->set('finish_at', now()->addMinutes(10)->toDateTimeString())
        ->set('direction', Call::INBOUND)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('direction', Call::OUTBOUND);

    $call = Call::where('name', 'They rang')->sole();

    expect($call->direction)->toBe(Call::INBOUND)->and($call->isOutbound())->toBeFalse();

    Livewire::test(CallItem::class, ['call' => $call])
        ->assertSee('Received')
        ->assertDontSee('Called')
        ->call('edit')
        ->assertSet('direction', Call::INBOUND)
        ->set('direction', Call::OUTBOUND)
        ->call('update')
        ->assertHasNoErrors()
        ->assertSee('Called');

    expect($call->fresh()->direction)->toBe(Call::OUTBOUND);
});

it('rejects an unknown call direction and treats older calls as called', function () {
    Livewire::test(CallRelated::class, ['model' => $this->sam])
        ->set('name', 'Bad')
        ->set('start_at', now()->toDateTimeString())
        ->set('finish_at', now()->toDateTimeString())
        ->set('direction', 'sideways')
        ->call('save')
        ->assertHasErrors(['direction' => 'in']);

    $old = $this->sam->calls()->create(['name' => 'Old call', 'start_at' => now(), 'finish_at' => now()]);

    expect($old->fresh()->isOutbound())->toBeTrue();
});
