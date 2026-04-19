<?php

namespace VentureDrake\LaravelCrm\Livewire\Tasks;

use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Task;

class TaskItem extends Component
{
    use Toast;

    public Task $task;

    public bool $related = false;

    public bool $editing = false;

    public string $name = '';

    public ?string $description = null;

    public ?string $due_at = null;

    public ?string $completed_at = null;

    public ?int $user_owner_id = null;

    public ?int $user_assigned_id = null;

    private array $revert = [];

    public function mount(Task $task, bool $related = false): void
    {
        $this->task = $task;
        $this->related = $related;
        $this->name = $task->name ?? '';
        $this->description = $task->description;
        $this->due_at = $task->due_at?->toDateTimeString();
        $this->completed_at = $task->completed_at?->toDateTimeString();
        $this->user_owner_id = $task->user_owner_id;
        $this->user_assigned_id = $task->user_assigned_id;
    }

    public function edit(): void
    {
        $this->revert = [
            'name' => $this->name,
            'description' => $this->description,
            'due_at' => $this->due_at,
            'user_owner_id' => $this->user_owner_id,
            'user_assigned_id' => $this->user_assigned_id,
        ];

        $this->editing = true;
    }

    public function cancel(): void
    {
        foreach ($this->revert as $key => $value) {
            $this->$key = $value;
        }
        $this->editing = false;
    }

    public function update(): void
    {
        $this->validate([
            'name' => 'required|max:255',
        ]);

        $this->task->update([
            'name' => $this->name,
            'description' => $this->description,
            'due_at' => $this->due_at,
            'user_owner_id' => $this->user_owner_id,
            'user_assigned_id' => $this->user_assigned_id,
        ]);

        $this->dispatch('task-updated');
        $this->dispatch('activity-logged');

        $this->success(ucfirst(trans('laravel-crm::lang.task_updated')));

        $this->editing = false;
    }

    public function complete(): void
    {
        $this->task->update(['completed_at' => now()]);
        $this->completed_at = $this->task->completed_at->toDateTimeString();

        $this->success(ucfirst(trans('laravel-crm::lang.task_completed')));

        $this->dispatch('task-updated');
        $this->dispatch('activity-logged');
    }

    public function delete(): void
    {
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
