<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Activities\ActivityFeed;
use VentureDrake\LaravelCrm\Livewire\Activities\ActivityIndex;
use VentureDrake\LaravelCrm\Livewire\Calls\CallRelated;
use VentureDrake\LaravelCrm\Livewire\Notes\NoteRelated;
use VentureDrake\LaravelCrm\Livewire\Settings\SettingEdit;
use VentureDrake\LaravelCrm\Livewire\Tasks\TaskRelated;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Support\ActivityOrder;

/*
 * Activity lists are ordered by when each activity was entered, or - with the
 * `activity_order` setting on `occurred` - by when it happened.
 */

beforeEach(function () {
    $this->user = $this->actingAsUser(['crm_access' => 1]);
    Gate::before(fn () => true);

    Setting::updateOrCreate(['name' => 'team'], ['value' => 'Acme Pty Ltd']);
    app('laravel-crm.settings')->forgetCache();

    $this->sam = Person::create(['first_name' => 'Sam', 'last_name' => 'Lee']);

    // Entered first, happened last - and the other way round.
    $this->enteredFirst = $this->sam->calls()->create(['name' => 'Entered first', 'start_at' => '2026-11-10 09:00:00', 'finish_at' => '2026-11-10 09:30:00']);
    $this->enteredFirst->forceFill(['created_at' => '2026-11-01 08:00:00'])->saveQuietly();

    $this->enteredLast = $this->sam->calls()->create(['name' => 'Entered last', 'start_at' => '2026-11-03 09:00:00', 'finish_at' => '2026-11-03 09:30:00']);
    $this->enteredLast->forceFill(['created_at' => '2026-11-05 08:00:00'])->saveQuietly();

    foreach ([$this->enteredFirst, $this->enteredLast] as $call) {
        $activity = $this->sam->activities()->create([
            'causeable_type' => $this->user->getMorphClass(),
            'causeable_id' => $this->user->id,
            'timelineable_type' => $this->sam->getMorphClass(),
            'timelineable_id' => $this->sam->id,
            'recordable_type' => $call->getMorphClass(),
            'recordable_id' => $call->id,
        ]);
        $activity->forceFill(['created_at' => $call->created_at])->saveQuietly();
    }
});

function setActivityOrder(string $order): void
{
    app('laravel-crm.settings')->set('activity_order', $order);
    app('laravel-crm.settings')->forgetCache();
}

it('lists activities by created time unless the setting says otherwise', function () {
    expect(ActivityOrder::setting())->toBe(ActivityOrder::LOGGED);

    setActivityOrder('nonsense');
    expect(ActivityOrder::setting())->toBe(ActivityOrder::LOGGED);

    setActivityOrder(ActivityOrder::OCCURRED);
    expect(ActivityOrder::byOccurrence())->toBeTrue();
});

it('orders a record\'s calls by the setting', function () {
    Livewire::test(CallRelated::class, ['model' => $this->sam])
        ->assertSeeInOrder(['Entered last', 'Entered first']);

    setActivityOrder(ActivityOrder::OCCURRED);

    Livewire::test(CallRelated::class, ['model' => $this->sam])
        ->assertSeeInOrder(['Entered first', 'Entered last']);
});

it('orders the timeline and the feed by the setting and shows the matching date', function () {
    $timeline = fn () => Livewire::test(ActivityIndex::class, ['model' => $this->sam])->instance()->activities()->pluck('recordable_id')->all();
    $feed = fn () => collect(Livewire::test(ActivityFeed::class, ['scope' => 'all'])->instance()->activities()->items())->pluck('recordable_id')->all();

    expect($timeline())->toBe([$this->enteredLast->id, $this->enteredFirst->id])
        ->and($feed())->toBe([$this->enteredLast->id, $this->enteredFirst->id]);

    Livewire::test(ActivityIndex::class, ['model' => $this->sam])->assertSee('11/05/2026 08:00 AM')->assertDontSee('11/10/2026 09:00 AM');

    setActivityOrder(ActivityOrder::OCCURRED);

    expect($timeline())->toBe([$this->enteredFirst->id, $this->enteredLast->id])
        ->and($feed())->toBe([$this->enteredFirst->id, $this->enteredLast->id]);

    Livewire::test(ActivityIndex::class, ['model' => $this->sam])->assertSee('11/10/2026 09:00 AM')->assertDontSee('11/05/2026 08:00 AM');
});

it('falls back to the created time for an activity with no date of its own', function () {
    $undated = $this->sam->notes()->create(['content' => 'Undated note', 'user_created_id' => $this->user->id]);
    $undated->forceFill(['created_at' => '2026-11-20 08:00:00'])->saveQuietly();

    $dated = $this->sam->notes()->create(['content' => 'Dated note', 'noted_at' => '2026-11-25 08:00:00', 'user_created_id' => $this->user->id]);
    $dated->forceFill(['created_at' => '2026-11-02 08:00:00'])->saveQuietly();

    setActivityOrder(ActivityOrder::OCCURRED);

    Livewire::test(NoteRelated::class, ['model' => $this->sam])
        ->assertSeeInOrder(['Dated note', 'Undated note']);
});

it('saves the activity order from the settings form', function () {
    Livewire::test(SettingEdit::class)
        ->assertSet('activityOrder', ActivityOrder::LOGGED)
        ->set(requiredActivityOrderSettings())
        ->set('activityOrder', ActivityOrder::OCCURRED)
        ->call('save')
        ->assertHasNoErrors();

    app('laravel-crm.settings')->forgetCache();
    expect(ActivityOrder::setting())->toBe(ActivityOrder::OCCURRED);

    Livewire::test(SettingEdit::class)
        ->set(requiredActivityOrderSettings())
        ->set('activityOrder', 'sideways')
        ->call('save')
        ->assertHasErrors(['activityOrder' => 'in']);
});

function requiredActivityOrderSettings(): array
{
    return ['organizationName' => 'Acme Pty Ltd', 'timezone' => 'UTC', 'dateFormat' => 'M j, Y', 'timeFormat' => 'H:i'];
}

it('orders a task by its completed date once completed, and by its due date until then', function () {
    $make = function (string $name, string $due, ?string $completed, string $created) {
        $task = $this->sam->tasks()->create(['name' => $name, 'due_at' => $due, 'completed_at' => $completed, 'user_created_id' => $this->user->id, 'user_owner_id' => $this->user->id, 'user_assigned_id' => $this->user->id]);
        $task->forceFill(['created_at' => $created])->saveQuietly();

        return $task;
    };

    $make('Open task', '2026-11-20 09:00:00', null, '2026-11-01 08:00:00');
    $make('Done early task', '2026-11-28 09:00:00', '2026-11-10 09:00:00', '2026-11-02 08:00:00');
    $done = $make('Done late task', '2026-11-05 09:00:00', '2026-11-25 09:00:00', '2026-11-03 08:00:00');

    // Logged: newest entry first, whatever the task's dates.
    Livewire::test(TaskRelated::class, ['model' => $this->sam])
        ->assertSeeInOrder(['Done late task', 'Done early task', 'Open task']);

    setActivityOrder(ActivityOrder::OCCURRED);

    Livewire::test(TaskRelated::class, ['model' => $this->sam])
        ->assertSeeInOrder(['Done late task', 'Open task', 'Done early task']);

    $activity = $this->sam->activities()->create([
        'causeable_type' => $this->user->getMorphClass(),
        'causeable_id' => $this->user->id,
        'timelineable_type' => $this->sam->getMorphClass(),
        'timelineable_id' => $this->sam->id,
        'recordable_type' => $done->getMorphClass(),
        'recordable_id' => $done->id,
    ]);

    expect(ActivityOrder::dateFor($activity)->format('Y-m-d H:i'))->toBe('2026-11-25 09:00')
        ->and(Livewire::test(ActivityIndex::class, ['model' => $this->sam])->instance()->activities()->first()->id)->toBe($activity->id);
});
