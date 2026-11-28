<?php

namespace VentureDrake\LaravelCrm\Livewire\Tasks;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Task;
use VentureDrake\LaravelCrm\Support\ActivityOrder;

class TaskRelated extends Component
{
    use AuthorizesRequests, Toast;

    public $model = null;

    public $tasks = [];

    public $name;

    public $description;

    public $start_at;

    public $due_at;

    public $user_owner_id;

    public $user_assigned_id;

    public $showForm = false;

    public array $data = [];

    public function mount(): void
    {
        $this->user_owner_id = auth()->user()->id;
        $this->user_assigned_id = auth()->user()->id;
    }

    public function save(): void
    {
        $this->authorize('create', Task::class);

        $this->validate([
            'name' => 'required|max:255',
            'description' => 'nullable',
            'start_at' => 'nullable',
            // Only compare against a start date the user actually entered: Laravel resolves a
            // null start_at to now(), which would forbid an otherwise valid past due date.
            'due_at' => array_filter([
                'nullable',
                filled($this->start_at) ? 'after_or_equal:start_at' : null,
            ]),
            'user_owner_id' => 'nullable',
            'user_assigned_id' => 'nullable',
        ]);

        $task = $this->model->tasks()->create([
            'name' => $this->name,
            'description' => $this->description,
            'start_at' => $this->normalizeDatetime($this->start_at),
            'due_at' => $this->normalizeDatetime($this->due_at),
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
    #[On('task-added')]
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
            $this->tasks = ActivityOrder::applyToRecords(Task::whereIn('id', $taskIds))->get();
        } else {
            $this->tasks = collect();
        }

        foreach ($this->tasks as $task) {
            $this->data[$task->id] = [
                'related' => in_array($task->id, $relatedIds),
            ];
        }
    }

    private function normalizeDatetime(?string $value): ?string
    {
        return $value ? str_replace('T', ' ', $value) : null;
    }

    private function resetFields(): void
    {
        $this->reset('name', 'description', 'start_at', 'due_at');
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
