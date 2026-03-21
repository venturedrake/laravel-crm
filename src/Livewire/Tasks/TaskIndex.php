<?php

namespace VentureDrake\LaravelCrm\Livewire\Tasks;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Task;
use VentureDrake\LaravelCrm\Traits\ClearsProperties;
use VentureDrake\LaravelCrm\Traits\ResetsPaginationWhenPropsChanges;

class TaskIndex extends Component
{
    use ClearsProperties, ResetsPaginationWhenPropsChanges, Toast, WithPagination;

    public $layout = 'index';

    #[Url]
    public string $search = '';

    #[Url]
    public ?array $user_id = [];

    #[Url]
    public ?string $status = null;

    #[Url]
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public bool $showFilters = false;

    public function filterCount(): int
    {
        return (count($this->user_id) > 0 ? 1 : 0) + ($this->status ? 1 : 0);
    }

    public function users(): Collection
    {
        return User::orderBy('name')->get();
    }

    public function headers(): array
    {
        return [
            ['key' => 'created_at', 'label' => ucfirst(__('laravel-crm::lang.created')), 'format' => fn ($row, $field) => $field->diffForHumans()],
            ['key' => 'completed_at', 'label' => ucfirst(__('laravel-crm::lang.status')), 'format' => fn ($row, $field) => $field ? $field->diffForHumans() : '-', 'sortable' => false],
            ['key' => 'name', 'label' => ucfirst(__('laravel-crm::lang.task'))],
            ['key' => 'description', 'label' => ucfirst(__('laravel-crm::lang.description'))],
            ['key' => 'due_at', 'label' => ucfirst(__('laravel-crm::lang.due')), 'format' => fn ($row, $field) => $field ? $field->diffForHumans() : '-'],
            ['key' => 'ownerUser.name', 'label' => ucfirst(__('laravel-crm::lang.created_by')), 'sortable' => false],
            ['key' => 'assignedToUser.name', 'label' => ucfirst(__('laravel-crm::lang.assigned_to')), 'sortable' => false],
        ];
    }

    public function tasks(): LengthAwarePaginator
    {
        return Task::query()
            ->when($this->search, fn (Builder $q) => $q->where('name', 'like', "%$this->search%")
                ->orWhere('description', 'like', "%$this->search%"))
            ->when($this->user_id, fn (Builder $q) => $q->whereIn('user_assigned_id', $this->user_id))
            ->when($this->status === 'completed', fn (Builder $q) => $q->whereNotNull('completed_at'))
            ->when($this->status === 'pending', fn (Builder $q) => $q->whereNull('completed_at'))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(25);
    }

    public function delete($id): void
    {
        if ($task = Task::find($id)) {
            $task->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.task_deleted')));
        }
    }

    public function complete($id): void
    {
        if ($task = Task::find($id)) {
            $task->update(['completed_at' => now()]);

            $this->success(ucfirst(trans('laravel-crm::lang.task_completed')));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.tasks.task-index', [
            'users' => $this->users(),
            'filterCount' => $this->filterCount(),
            'headers' => $this->headers(),
            'tasks' => $this->tasks(),
        ]);
    }
}
