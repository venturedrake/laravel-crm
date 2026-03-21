<?php

namespace VentureDrake\LaravelCrm\Livewire\Tasks;

use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Task;

class TaskShow extends Component
{
    use Toast;

    public Task $task;

    public function mount(Task $task)
    {
        $this->task = $task;
    }

    public function complete(): void
    {
        $this->task->update(['completed_at' => now()]);

        $this->success(ucfirst(trans('laravel-crm::lang.task_completed')));
    }

    public function delete($id): void
    {
        if ($task = Task::find($id)) {
            $task->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.task_deleted')), redirectTo: route('laravel-crm.tasks.index'));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.tasks.task-show');
    }
}
