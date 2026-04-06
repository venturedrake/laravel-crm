<?php

namespace VentureDrake\LaravelCrm\Livewire\Tasks;

use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Task;

class TaskRelated extends Component
{
    use Toast;

    public $model = null;

    public $tasks = [];

    public $name;

    public $description;

    public $due_at;

    public $user_owner_id;

    public $user_assigned_id;

    public $showForm = false;

    public array $data = [];

    public array $revert = [];

    public function mount(): void
    {
        $this->user_owner_id = auth()->user()->id;
        $this->user_assigned_id = auth()->user()->id;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|max:255',
            'description' => 'nullable',
            'due_at' => 'nullable',
            'user_owner_id' => 'nullable',
            'user_assigned_id' => 'nullable',
        ]);

        $task = $this->model->tasks()->create([
            'name' => $this->name,
            'description' => $this->description,
            'due_at' => $this->due_at,
            'user_owner_id' => $this->user_owner_id,
            'user_assigned_id' => $this->user_assigned_id,
        ]);

        $this->model->activities()->create([
            'causeable_type' => auth()->user()->getMorphClass(),
            'causeable_id' => auth()->user()->id,
            'timelineable_type' => $this->model->getMorphClass(),
            'timelineable_id' => $this->model->id,
            'recordable_type' => $task->getMorphClass(),
            'recordable_id' => $task->id,
        ]);

        $this->dispatch('task-added');
        $this->dispatch('activity-logged');

        $this->success(
            ucfirst(trans('laravel-crm::lang.task_created'))
        );

        $this->resetFields();
    }

    #[On('task-updated')]
    public function getTasks(): void
    {
        $taskIds = [];
        $relatedIds = [];

        foreach ($this->model->tasks()->latest()->get() as $task) {
            $taskIds[] = $task->id;
        }

        if (app('laravel-crm.settings')->get('show_related_activity') == 1) {
            if (method_exists($this->model, 'contacts')) {
                foreach ($this->model->contacts as $contact) {
                    foreach ($contact->entityable->tasks()->latest()->get() as $task) {
                        $taskIds[] = $task->id;
                        $relatedIds[] = $task->id;
                    }
                }
            }

            if (method_exists($this->model, 'organization') && $this->model->organization) {
                foreach ($this->model->organization->tasks()->latest()->get() as $task) {
                    $taskIds[] = $task->id;
                    $relatedIds[] = $task->id;
                }
            }

            if (method_exists($this->model, 'person') && $this->model->person) {
                foreach ($this->model->person->tasks()->latest()->get() as $task) {
                    $taskIds[] = $task->id;
                    $relatedIds[] = $task->id;
                }
            }
        }

        if (count($taskIds) > 0) {
            $this->tasks = Task::whereIn('id', $taskIds)->latest()->get();
        } else {
            $this->tasks = collect();
        }

        foreach ($this->tasks as $task) {
            $this->data[$task->id] = array_merge([
                'editing' => false,
            ], $this->data[$task->id] ?? [], [
                'id' => $task->id,
                'name' => $task->name,
                'description' => $task->description,
                'due_at' => $task->due_at ? $task->due_at->toDateTimeString() : null,
                'completed_at' => $task->completed_at ? $task->completed_at->toDateTimeString() : null,
                'user_owner_id' => $task->user_owner_id,
                'user_assigned_id' => $task->user_assigned_id,
                'related' => in_array($task->id, $relatedIds),
            ]);
        }
    }

    public function edit($id): void
    {
        $this->revert[$id] = $this->data[$id];
        $this->data[$id]['editing'] = true;
    }

    public function cancel($id): void
    {
        $this->data[$id]['editing'] = false;
        $this->data[$id] = $this->revert[$id];
    }

    public function update($id): void
    {
        $this->validate([
            'data.'.$id.'.name' => 'required|max:255',
        ]);

        if ($task = $this->model->tasks()->find($id)) {
            $task->update([
                'name' => $this->data[$id]['name'],
                'description' => $this->data[$id]['description'],
                'due_at' => $this->data[$id]['due_at'],
                'user_owner_id' => $this->data[$id]['user_owner_id'],
                'user_assigned_id' => $this->data[$id]['user_assigned_id'],
            ]);
        }

        $this->dispatch('task-updated');

        $this->success(
            ucfirst(trans('laravel-crm::lang.task_updated'))
        );

        $this->data[$id]['editing'] = false;
    }

    public function complete($id): void
    {
        if ($task = $this->model->tasks()->find($id)) {
            $task->update(['completed_at' => now()]);

            $this->success(ucfirst(trans('laravel-crm::lang.task_completed')));
        }
    }

    public function delete($id): void
    {
        if ($task = $this->model->tasks()->find($id)) {
            $task->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.task_deleted')));
        }
    }

    private function resetFields(): void
    {
        $this->reset('name', 'description', 'due_at');
        $this->user_owner_id = auth()->user()->id;
        $this->user_assigned_id = auth()->user()->id;
    }

    public function render()
    {
        $this->getTasks();

        return view('laravel-crm::livewire.tasks.task-related', [
            'users' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\usersOptions(false),
        ]);
    }
}
