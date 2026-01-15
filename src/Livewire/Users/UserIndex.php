<?php

namespace VentureDrake\LaravelCrm\Livewire\Users;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Traits\ClearsProperties;
use VentureDrake\LaravelCrm\Traits\ResetsPaginationWhenPropsChanges;

class UserIndex extends Component
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

    public $dateFormat;

    public function mount()
    {
        $this->dateFormat = app('laravel-crm.settings')->get('date_format', config('laravel-crm.date_format'));
    }

    public function filterCount(): int
    {
        return (count($this->user_id) > 0 ? 1 : 0) + ($this->label_id ? 1 : 0);
    }

    public function ownerUsers(): Collection
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
            ['key' => 'email', 'label' => ucfirst(__('laravel-crm::lang.email'))],
            ['key' => 'email_verified_at', 'label' => ucwords(__('laravel-crm::lang.email_verified')), 'format' => fn ($row, $field) => ($field) ? $field->format($this->dateFormat) : null],
            ['key' => 'crm_access', 'label' => ucfirst(__('laravel-crm::lang.CRM_Access')), 'format' => fn ($row, $field) => $field ? ucfirst(__('laravel-crm::lang.yes')) : ucfirst(__('laravel-crm::lang.no'))],
            ['key' => 'role', 'label' => ucfirst(__('laravel-crm::lang.role')), 'sortable' => false],
            ['key' => 'ownerUser.name', 'label' => 'Owner', 'format' => fn ($row, $field) => $field ?? ucfirst(__('laravel-crm::lang.unallocated')), 'sortable' => false],
            ['key' => 'created_at', 'label' => ucfirst(__('laravel-crm::lang.created')), 'format' => fn ($row, $field) => $field->diffForHumans()],
            ['key' => 'last_online_at', 'label' => ucwords(__('laravel-crm::lang.last_online')), 'format' => fn ($row, $field) => ($field) ? \Carbon\Carbon::parse($field)->diffForHumans() : ucfirst(__('laravel-crm::lang.never'))],

        ];
    }

    public function users(): LengthAwarePaginator
    {
        return User::when($this->search, function (Builder $q) {
            $q->where('name', 'like', "%$this->search%");
        })->when($this->user_id, fn (Builder $q) => $q->whereIn('user_owner_id', $this->user_id))
            ->when($this->label_id, fn (Builder $q) => $q->whereHas('labels', fn (Builder $q) => $q->whereIn('labels.id', $this->label_id)))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(25);
    }

    public function delete($id)
    {
        if ($user = User::find($id)) {
            $user->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.user_deleted')));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.users.user-index', [
            'ownerUsers' => $this->ownerUsers(),
            'labels' => $this->labels(),
            'filterCount' => $this->filterCount(),
            'headers' => $this->headers(),
            'users' => $this->users(),
        ]);
    }
}
