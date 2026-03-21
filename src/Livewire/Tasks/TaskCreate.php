<?php

namespace VentureDrake\LaravelCrm\Livewire\Tasks;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Tasks\Traits\HasTaskCommon;

class TaskCreate extends Component
{
    use HasTaskCommon;

    public function mount()
    {
        $this->user_owner_id = auth()->user()->id;
        $this->user_assigned_id = auth()->user()->id;
    }

    public function save()
    {
        $this->validate();

        $request = \VentureDrake\LaravelCrm\Http\Helpers\PublicProperties\asRequest($this);

        $this->taskService->create($request);

        $this->success(
            ucfirst(trans('laravel-crm::lang.task_created')),
            redirectTo: route('laravel-crm.tasks.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.tasks.task-create');
    }
}
