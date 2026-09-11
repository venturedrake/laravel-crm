<?php

namespace VentureDrake\LaravelCrm\Livewire\Organizations;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Livewire\Traits\SearchesEncryptableContacts;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Traits\ClearsProperties;
use VentureDrake\LaravelCrm\Traits\ResetsPaginationWhenPropsChanges;

class OrganizationIndex extends Component
{
    use AuthorizesRequests, ClearsProperties, ResetsPaginationWhenPropsChanges, SearchesEncryptableContacts, Toast, WithPagination;

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

    /**
     * Filter drawer options, computed so a debounced search keystroke reuses
     * them rather than re-querying users and labels on every render.
     */
    #[Computed]
    public function users(): Collection
    {
        return User::orderBy('name')->get();
    }

    #[Computed]
    public function labels(): Collection
    {
        return Label::all();
    }

    public function headers()
    {
        return [
            ['key' => 'name', 'label' => ucfirst(__('laravel-crm::lang.name'))],
            ['key' => 'xeroContact', 'label' => '', 'sortable' => false],
            ['key' => 'organizationType.name', 'label' => ucfirst(__('laravel-crm::lang.type')), 'sortable' => false],
            ['key' => 'labels', 'label' => ucfirst(__('laravel-crm::lang.labels')), 'format' => fn ($row, $field) => $field, 'sortable' => false],
            ['key' => 'open_deals', 'label' => ucfirst(__('laravel-crm::lang.open_deals')), 'sortable' => false],
            ['key' => 'lost_deals', 'label' => ucfirst(__('laravel-crm::lang.lost_deals')), 'sortable' => false],
            ['key' => 'won_deals', 'label' => ucfirst(__('laravel-crm::lang.won_deals')), 'sortable' => false],
            ['key' => 'ownerUser.name', 'label' => 'Owner', 'format' => fn ($row, $field) => $field ?? ucfirst(__('laravel-crm::lang.unallocated')), 'sortable' => false],
            ['key' => 'created_at', 'label' => ucfirst(__('laravel-crm::lang.created')), 'format' => fn ($row, $field) => $field->diffForHumans()],
        ];
    }

    public function organizations(): LengthAwarePaginator
    {
        // Same shape as PersonIndex: without these the table costs five queries
        // per row for the Xero badge, type, labels, deal counts and owner.
        return Organization::with(['labels', 'ownerUser', 'organizationType', 'xeroContact'])
            ->withCount([
                'deals as open_deals_count' => fn ($q) => $q->whereNull('closed_at'),
                'deals as lost_deals_count' => fn ($q) => $q->where('closed_status', 'lost'),
                'deals as won_deals_count' => fn ($q) => $q->where('closed_status', 'won'),
            ])
            ->when($this->search, function (Builder $q) {
                if ($this->encryptionEnabled()) {
                    $ids = $this->matchingOrganizationIds($this->search);
                    $q->whereIn('id', $ids->isEmpty() ? [0] : $ids);
                } else {
                    $q->where('name', 'like', "%$this->search%");
                }
            })
            ->when($this->user_id, fn (Builder $q) => $q->whereIn('user_owner_id', $this->user_id))
            ->when($this->label_id, fn (Builder $q) => $q->whereHas('labels', fn (Builder $q) => $q->whereIn(config('laravel-crm.db_table_prefix').'labels.id', $this->label_id)))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(25);
    }

    public function delete($id)
    {
        if ($organization = Organization::find($id)) {
            $this->authorize('delete', $organization);

            $organization->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.organization_deleted')));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.organizations.organization-index', [
            'users' => $this->users,
            'labels' => $this->labels,
            'filterCount' => $this->filterCount(),
            'headers' => $this->headers(),
            'organizations' => $this->organizations(),
        ]);
    }
}
