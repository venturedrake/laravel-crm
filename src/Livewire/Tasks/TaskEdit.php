<?php

namespace VentureDrake\LaravelCrm\Livewire\Tasks;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Tasks\Traits\HasTaskCommon;
use VentureDrake\LaravelCrm\Models\Task;

class TaskEdit extends Component
{
    use AuthorizesRequests, HasTaskCommon;

    public ?Task $task = null;

    public function mount(Task $task)
    {
        $this->task = $task;
        $this->name = $task->name;
        $this->description = $task->description;
        $this->start_at = $task->start_at?->format('Y-m-d\TH:i');
        $this->due_at = $task->due_at?->format('Y-m-d\TH:i');
        $this->user_owner_id = $task->user_owner_id;
        $this->user_assigned_id = $task->user_assigned_id;

        $this->loadCustomFields($task);
    }

    public function save()
    {
        $this->authorize('update', $this->task);

        $this->validate();

        $request = \VentureDrake\LaravelCrm\Http\Helpers\PublicProperties\asRequest($this);

        $this->taskService->update($request, $this->task);

        $this->saveCustomFields($this->task->fresh());

        $this->success(
            ucfirst(trans('laravel-crm::lang.task_updated')),
            redirectTo: route('laravel-crm.tasks.show', $this->task)
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.tasks.task-edit');
    }
}
