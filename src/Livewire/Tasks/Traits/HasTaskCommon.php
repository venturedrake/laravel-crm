<?php

namespace VentureDrake\LaravelCrm\Livewire\Tasks\Traits;

use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Task;
use VentureDrake\LaravelCrm\Services\TaskService;
use VentureDrake\LaravelCrm\Traits\HasCustomFormFields;

trait HasTaskCommon
{
    use HasCustomFormFields;
    use Toast;

    protected TaskService $taskService;

    public $name;

    public $description;

    public $due_at;

    public $user_owner_id;

    public $user_assigned_id;

    protected function customFieldsModel(): string
    {
        return Task::class;
    }

    protected function rules()
    {
        return array_merge([
            'name' => 'required|max:255',
            'description' => 'nullable',
            'due_at' => 'nullable',
            'user_owner_id' => 'nullable',
            'user_assigned_id' => 'nullable',
        ], $this->customFieldRules());
    }

    protected function messages()
    {
        return $this->customFieldMessages();
    }

    protected function validationAttributes()
    {
        return $this->customFieldValidationAttributes();
    }

    public function boot(TaskService $taskService): void
    {
        $this->taskService = $taskService;
    }
}
