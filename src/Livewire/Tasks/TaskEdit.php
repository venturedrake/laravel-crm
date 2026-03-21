<?php

namespace VentureDrake\LaravelCrm\Livewire\Tasks;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Tasks\Traits\HasTaskCommon;
use VentureDrake\LaravelCrm\Models\Task;

class TaskEdit extends Component
{
    use HasTaskCommon;

    public ?Task $task = null;

    public function mount(Task $task)
    {
        $this->task = $task;
        $this->name = $task->name;
        $this->description = $task->description;
        $this->due_at = $task->due_at ? $task->due_at->format('Y-m-d') : null;
        $this->user_owner_id = $task->user_owner_id;
        $this->user_assigned_id = $task->user_assigned_id;
    }

    public function save()
    {
        $this->validate();

        $request = \VentureDrake\LaravelCrm\Http\Helpers\PublicProperties\asRequest($this);

        $this->taskService->update($request, $this->task);

        $this->success(
            ucfirst(trans('laravel-crm::lang.task_updated')),
            redirectTo: route('laravel-crm.tasks.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.tasks.task-edit');
    }
}
