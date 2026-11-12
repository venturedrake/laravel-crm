<?php

namespace VentureDrake\LaravelCrm\Livewire\Tasks;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Task;

class TaskItem extends Component
{
    use AuthorizesRequests, Toast;

    public Task $task;

    public bool $related = false;

    public bool $editing = false;

    /**
     * Whether the item is opened to its full details. Bound to the collapse
     * widget, so it survives the list around the item being refreshed.
     */
    public bool $expanded = false;

    public string $name = '';

    public ?string $description = null;

    public ?string $start_at = null;

    public ?string $due_at = null;

    public ?string $completed_at = null;

    public ?int $user_owner_id = null;

    public ?int $user_assigned_id = null;

    public function mount(Task $task, bool $related = false): void
    {
        $this->task = $task;
        $this->related = $related;

        $this->hydrateFromRecord();
    }

    /**
     * Fill the form fields from the stored record.
     *
     * Cancelling restores from the record rather than from a snapshot taken in edit():
     * edit() and cancel() are separate requests, so a snapshot would have to survive the
     * round trip on a public property. Reading the record back is both cheaper and
     * correct when someone else has edited it in the meantime.
     */
    private function hydrateFromRecord(): void
    {
        $this->name = $this->task->name ?? '';
        $this->description = $this->task->description;
        // Both are bound to datetime-local inputs, which only accept Y-m-d\TH:i.
        $this->start_at = $this->task->start_at?->format('Y-m-d\TH:i');
        $this->due_at = $this->task->due_at?->format('Y-m-d\TH:i');
        $this->completed_at = $this->task->completed_at?->toDateTimeString();
        $this->user_owner_id = $this->task->user_owner_id;
        $this->user_assigned_id = $this->task->user_assigned_id;
    }

    public function edit(): void
    {
        $this->editing = true;
        $this->expanded = true;
    }

    public function cancel(): void
    {
        $this->hydrateFromRecord();
        $this->editing = false;
    }

    /**
     * Another copy of this task on the page was changed (the same task is
     * shown in its own tab and on the timeline): show what was saved. The
     * record itself is re-read on every request; only the form fields need
     * filling again, and not while they are being edited here.
     */
    #[On('task-updated.{task.id}')]
    public function refreshFromRecord(): void
    {
        if (! $this->editing) {
            $this->hydrateFromRecord();
        }
    }

    public function update(): void
    {
        $this->authorize('update', $this->task);

        $this->validate([
            'name' => 'required|max:255',
            'start_at' => 'nullable',
            // Only compare against a start date the user actually entered: Laravel resolves a
            // null start_at to now(), which would forbid an otherwise valid past due date.
            'due_at' => array_filter([
                'nullable',
                filled($this->start_at) ? 'after_or_equal:start_at' : null,
            ]),
        ]);

        $this->task->update([
            'name' => $this->name,
            'description' => $this->description,
            'start_at' => $this->normalizeDatetime($this->start_at),
            'due_at' => $this->normalizeDatetime($this->due_at),
            'user_owner_id' => $this->user_owner_id,
            'user_assigned_id' => $this->user_assigned_id,
        ]);

        $this->dispatch('task-updated');
        $this->dispatch('task-updated.'.$this->task->id);
        $this->dispatch('activity-logged');

        $this->success(ucfirst(trans('laravel-crm::lang.task_updated')));

        $this->editing = false;
    }

    private function normalizeDatetime(?string $value): ?string
    {
        return $value ? str_replace('T', ' ', $value) : null;
    }

    public function complete(): void
    {
        $this->authorize('update', $this->task);

        $this->task->update(['completed_at' => now()]);
        $this->completed_at = $this->task->completed_at->toDateTimeString();

        $this->success(ucfirst(trans('laravel-crm::lang.task_completed')));

        $this->dispatch('task-updated');
        $this->dispatch('task-updated.'.$this->task->id);
        $this->dispatch('activity-logged');
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->task);

        $this->task->delete();

        $this->success(ucfirst(trans('laravel-crm::lang.task_deleted')));

        $this->dispatch('task-updated');
        $this->dispatch('activity-logged');
    }

    public function render()
    {
        return view('laravel-crm::livewire.tasks.task-item', [
            'users' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\usersOptions(false),
        ]);
    }
}
