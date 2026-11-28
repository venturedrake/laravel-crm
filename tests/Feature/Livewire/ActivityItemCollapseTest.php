<?php

/*
 * Notes, tasks, calls, meetings and lunches are listed collapsed to a summary
 * and open to their details; Delete sits on the edit form and asks for the
 * word "confirm"; and a copy of a record shown elsewhere on the page follows
 * an edit made to it.
 */

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Calls\CallItem;
use VentureDrake\LaravelCrm\Livewire\Lunches\LunchItem;
use VentureDrake\LaravelCrm\Livewire\Meetings\MeetingItem;
use VentureDrake\LaravelCrm\Livewire\Notes\NoteItem;
use VentureDrake\LaravelCrm\Livewire\Tasks\TaskItem;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Setting;

dataset('activity items', [
    'note' => [NoteItem::class, 'note', 'notes', 'modalDeleteNoteItem'],
    'task' => [TaskItem::class, 'task', 'tasks', 'modalDeleteTaskItem'],
    'call' => [CallItem::class, 'call', 'calls', 'modalDeleteCallItem'],
    'meeting' => [MeetingItem::class, 'meeting', 'meetings', 'modalDeleteMeetingItem'],
    'lunch' => [LunchItem::class, 'lunch', 'lunches', 'modalDeleteLunchItem'],
]);

beforeEach(function () {
    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);
    Setting::updateOrCreate(['name' => 'team'], ['value' => 'related']);

    $this->person = Person::create(['first_name' => 'Sam', 'last_name' => 'Lee']);
});

function activityRecord(Person $person, string $relation)
{
    return match ($relation) {
        'notes' => $person->notes()->create(['content' => 'Summary line of the note. '.str_repeat('Filler so the detail falls outside the summary. ', 4).'Hidden detail of the note', 'user_created_id' => auth()->id()]),
        'tasks' => $person->tasks()->create(['name' => 'Summary of the task', 'description' => 'Hidden detail of the task']),
        default => $person->{$relation}()->create([
            'name' => 'Summary of the '.$relation,
            'description' => 'Hidden detail of the '.$relation,
            'start_at' => now()->toDateTimeString(),
            'finish_at' => now()->addHour()->toDateTimeString(),
        ]),
    };
}

it('lists an activity collapsed and opens it to its details', function (string $component, string $property, string $relation) {
    $this->actingAsUserWithPermissions(["view crm {$relation}"]);
    $record = activityRecord($this->person, $relation);

    Livewire::test($component, [$property => $record])
        ->assertSet('expanded', false)
        ->assertSee('Summary')
        ->assertDontSee('Hidden detail')
        ->assertSeeHtml('wire:model.live="expanded"')
        ->set('expanded', true)
        ->assertSee('Hidden detail');
})->with('activity items');

it('opens an activity when it is edited and keeps it open after saving', function (string $component, string $property, string $relation) {
    $this->actingAsUserWithPermissions(["view crm {$relation}", "edit crm {$relation}"]);
    $record = activityRecord($this->person, $relation);

    Livewire::test($component, [$property => $record])
        ->call('edit')
        ->assertSet('expanded', true)
        ->assertSet('editing', true)
        ->call('update')
        ->assertHasNoErrors()
        ->assertSet('expanded', true)
        ->assertSet('editing', false);
})->with('activity items');

it('offers Delete on the edit form, not in the menu, behind a typed confirmation', function (string $component, string $property, string $relation, string $dialog) {
    $this->actingAsUserWithPermissions(["view crm {$relation}", "edit crm {$relation}", "delete crm {$relation}"]);
    $record = activityRecord($this->person, $relation);

    $closed = Livewire::test($component, [$property => $record]);

    // Collapsed: the dialog is there, nothing on the page opens it.
    $closed->assertDontSeeHtml($dialog.$record->id.'.showModal()')
        ->assertSeeHtml('x-bind:disabled="typed.trim().toLowerCase() !== \'confirm\'"')
        ->assertSeeHtml('wire:click="delete"');

    // Editing: one opener, the Delete button beside Cancel.
    $html = $closed->call('edit')->html();

    // (Mary's button prints the attribute twice on the one element.)
    expect(substr_count($html, '<button'))->toBeGreaterThan(0)
        ->and(str_contains($html, $dialog.$record->id.'.showModal()'))->toBeTrue()
        ->and(strpos($html, $dialog.$record->id.'.showModal()'))->toBeLessThan(strpos($html, 'wire:click="cancel"'));
})->with('activity items');

it('hides Delete from someone who may edit but not delete', function (string $component, string $property, string $relation, string $dialog) {
    $this->actingAsUserWithPermissions(["view crm {$relation}", "edit crm {$relation}"]);
    $record = activityRecord($this->person, $relation);

    Livewire::test($component, [$property => $record])
        ->call('edit')
        ->assertDontSeeHtml($dialog)
        ->call('delete')
        ->assertForbidden();
})->with('activity items');

it('tells other copies of the same record that it changed, and they follow', function (string $component, string $property, string $relation) {
    $this->actingAsUserWithPermissions(["view crm {$relation}", "edit crm {$relation}"]);
    $record = activityRecord($this->person, $relation);
    $field = $relation === 'notes' ? 'content' : 'name';

    // The copy in the tab is edited…
    Livewire::test($component, [$property => $record])
        ->call('edit')
        ->set($field, 'Changed elsewhere')
        ->call('update')
        ->assertDispatched("{$property}-updated")
        ->assertDispatched("{$property}-updated.{$record->id}");

    // …and the copy on the timeline, mounted before the change, picks it up.
    $stale = Livewire::test($component, [$property => activityRecord($this->person, $relation)]);
    $other = $stale->instance()->{$property};
    $other->update([$field => 'Saved by another copy']);

    $stale->dispatch("{$property}-updated.{$other->id}")->assertSet($field, 'Saved by another copy');
})->with('activity items');

it('does not overwrite a form that is being edited when the record changes elsewhere', function (string $component, string $property, string $relation) {
    $this->actingAsUserWithPermissions(["view crm {$relation}", "edit crm {$relation}"]);
    $record = activityRecord($this->person, $relation);
    $field = $relation === 'notes' ? 'content' : 'name';

    Livewire::test($component, [$property => $record])
        ->call('edit')
        ->set($field, 'My unsaved change')
        ->dispatch("{$property}-updated.{$record->id}")
        ->assertSet($field, 'My unsaved change');
})->with('activity items');

it('does not listen for a change to a different record', function (string $component, string $property, string $relation) {
    $this->actingAsUserWithPermissions(["view crm {$relation}"]);
    $record = activityRecord($this->person, $relation);

    // Each item listens under its own record's id only, so an edit to one
    // activity does not make every item on the page ask the server again.
    expect(fn () => Livewire::test($component, [$property => $record])->dispatch("{$property}-updated.".($record->id + 999)))
        ->toThrow(Exception::class, 'does not exist');
})->with('activity items');
