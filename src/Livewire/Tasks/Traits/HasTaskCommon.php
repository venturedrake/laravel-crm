<?php

namespace VentureDrake\LaravelCrm\Livewire\Tasks\Traits;

use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Services\TaskService;

trait HasTaskCommon
{
    use Toast;

    protected TaskService $taskService;

    public $name;

    public $description;

    public $due_at;

    public $user_owner_id;

    public $user_assigned_id;

    protected function rules()
    {
        return [
            'name' => 'required|max:255',
            'description' => 'nullable',
            'due_at' => 'nullable',
            'user_owner_id' => 'nullable',
            'user_assigned_id' => 'nullable',
        ];
    }

    public function boot(TaskService $taskService): void
    {
        $this->taskService = $taskService;
    }
}

