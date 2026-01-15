<?php

namespace VentureDrake\LaravelCrm\Livewire\Teams;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Team;
use VentureDrake\LaravelCrm\Traits\ClearsProperties;
use VentureDrake\LaravelCrm\Traits\ResetsPaginationWhenPropsChanges;

class TeamIndex extends Component
{
    use ClearsProperties, ResetsPaginationWhenPropsChanges, Toast, WithPagination;

    public $layout = 'index';

    #[Url]
    public string $search = '';

    #[Url]
    public ?array $user_id = [];

    #[Url]
    public ?array $label_id = [];

    #[Url]
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public bool $showFilters = false;

    public function filterCount(): int
    {
        return (count($this->user_id) > 0 ? 1 : 0) + ($this->label_id ? 1 : 0);
    }

    public function users(): Collection
    {
        return User::orderBy('name')->get();
    }

    public function labels(): Collection
    {
        return Label::all();
    }

    public function headers()
    {
        return [
            ['key' => 'name', 'label' => ucfirst(__('laravel-crm::lang.name'))],
            ['key' => 'users', 'label' => ucfirst(__('laravel-crm::lang.users')), 'format' => fn ($row, $field) => count($field)],
            ['key' => 'userCreated.name', 'label' => ucfirst(__('laravel-crm::lang.created_by')), 'format' => fn ($row, $field) => $field ?? null, 'sortable' => false],
            ['key' => 'ownerUser.name', 'label' => ucfirst(__('laravel-crm::lang.owner')), 'format' => fn ($row, $field) => $field ?? ucfirst(__('laravel-crm::lang.unallocated')), 'sortable' => false],
            ['key' => 'created_at', 'label' => ucfirst(__('laravel-crm::lang.created')), 'format' => fn ($row, $field) => $field->diffForHumans()],
        ];
    }

    public function teams(): LengthAwarePaginator
    {
        return Team::when($this->search, function (Builder $q) {
            $q->where('name', 'like', "%$this->search%");
        })->when($this->user_id, fn (Builder $q) => $q->whereIn('team_owner_id', $this->user_id))
            ->when($this->label_id, fn (Builder $q) => $q->whereHas('labels', fn (Builder $q) => $q->whereIn('labels.id', $this->label_id)))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(25);
    }

    public function delete($id)
    {
        if ($team = Team::find($id)) {
            $team->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.team_deleted')));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.teams.team-index', [
            'users' => $this->users(),
            'labels' => $this->labels(),
            'filterCount' => $this->filterCount(),
            'headers' => $this->headers(),
            'teams' => $this->teams(),
        ]);
    }
}
