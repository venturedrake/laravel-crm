<?php

namespace VentureDrake\LaravelCrm\Livewire\People;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Livewire\Traits\HasUserOwnerFilter;
use VentureDrake\LaravelCrm\Livewire\Traits\SearchesEncryptableContacts;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Support\Modules;
use VentureDrake\LaravelCrm\Traits\ClearsProperties;
use VentureDrake\LaravelCrm\Traits\ResetsPaginationWhenPropsChanges;

class PersonIndex extends Component
{
    use AuthorizesRequests, ClearsProperties, HasUserOwnerFilter, ResetsPaginationWhenPropsChanges, SearchesEncryptableContacts, Toast, WithPagination;

    public $layout = 'index';

    #[Url]
    public string $search = '';

    #[Url]
    public ?array $user_id = [];

    #[Url]
    public ?array $label_id = [];

    /** Filter: people whose current position title contains this text. */
    #[Url]
    public string $position_title = '';

    #[Url]
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public bool $showFilters = false;

    public function filterCount(): int
    {
        return (count($this->user_id) > 0 ? 1 : 0) + ($this->label_id ? 1 : 0) + ($this->positionsEnabled() && trim($this->position_title) !== '' ? 1 : 0);
    }

    /**
     * Label options for the filter drawer.
     *
     * Computed rather than a plain method so a debounced search keystroke —
     * which re-renders the whole component — reuses it instead of re-running
     * the query on every hydration.
     */
    #[Computed]
    public function labels(): Collection
    {
        return Label::select('id', 'name')->get();
    }

    public function positionsEnabled(): bool
    {
        return Modules::enabled('positions');
    }

    public function headers()
    {
        return [
            ['key' => 'name', 'label' => ucfirst(__('laravel-crm::lang.name'))],
            ...($this->positionsEnabled() ? [['key' => 'current_position_title', 'label' => ucfirst(__('laravel-crm::lang.position')), 'sortable' => false]] : []),
            ['key' => 'labels', 'label' => ucfirst(__('laravel-crm::lang.labels')), 'format' => fn ($row, $field) => $field, 'sortable' => false],
            ['key' => 'email', 'label' => ucfirst(__('laravel-crm::lang.email')), 'sortable' => false],
            ['key' => 'phone', 'label' => ucfirst(__('laravel-crm::lang.phone')), 'sortable' => false],
            ['key' => 'open_deals', 'label' => ucfirst(__('laravel-crm::lang.open_deals')), 'sortable' => false],
            ['key' => 'lost_deals', 'label' => ucfirst(__('laravel-crm::lang.lost_deals')), 'sortable' => false],
            ['key' => 'won_deals', 'label' => ucfirst(__('laravel-crm::lang.won_deals')), 'sortable' => false],
            ['key' => 'ownerUser.name', 'label' => 'Owner', 'format' => fn ($row, $field) => $field ?? ucfirst(__('laravel-crm::lang.unallocated')), 'sortable' => false],
            ['key' => 'created_at', 'label' => ucfirst(__('laravel-crm::lang.created')), 'format' => fn ($row, $field) => $field->diffForHumans()],
        ];
    }

    public function people(): LengthAwarePaginator
    {
        $sortColumn = $this->sortBy['column'] === 'name' ? 'last_name' : $this->sortBy['column'];
        $sortDirection = $this->sortBy['direction'] ?? 'asc';

        // Every column the table renders is either a plain attribute or is
        // loaded here. Without this the 25-row page costs five queries per row:
        // labels, the primary email, the primary phone, the whole deals
        // collection (filtered three times in PHP) and the owner, which MaryUI
        // resolves with data_get($row, 'ownerUser.name').
        //
        // The eager loads deliberately sit on the paginated query and not on
        // the ID resolution SearchesEncryptableContacts does — that one walks
        // the whole people table to decrypt names, and relations on it would be
        // loaded for every row in the database rather than for the 25 shown.
        $positions = $this->positionsEnabled();
        $positionTitle = $positions ? trim($this->position_title) : '';

        return Person::with(['labels', 'ownerUser', 'primaryEmail', 'primaryPhone', ...($positions ? ['currentPositions'] : [])])
            ->withCount([
                'deals as open_deals_count' => fn ($q) => $q->whereNull('closed_at'),
                'deals as lost_deals_count' => fn ($q) => $q->where('closed_status', 'lost'),
                'deals as won_deals_count' => fn ($q) => $q->where('closed_status', 'won'),
                // Back deleteWarning() in each row's delete dialog.
                'leads', 'deals', 'quotes', 'orders', 'invoices', 'purchaseOrders',
            ])
            ->when($this->search, function (Builder $q) use ($positions) {
                $prefix = config('laravel-crm.db_table_prefix');
                $term = $this->search;

                // A current position title also matches, so "CFO" finds the
                // person without knowing their name. Titles are never
                // encrypted, so this half is plain SQL either way.
                $byPosition = fn (Builder $q) => $q->where('title', 'like', "%$term%");

                if ($this->encryptionEnabled()) {
                    // Names are stored encrypted; resolve matching IDs in PHP.
                    $ids = $this->matchingPersonIds($term);
                    $q->where(function ($q) use ($prefix, $ids, $positions, $byPosition) {
                        $q->whereIn($prefix.'people.id', $ids->isEmpty() ? [0] : $ids);

                        if ($positions) {
                            $q->orWhereHas('currentPositions', $byPosition);
                        }
                    });
                } else {
                    $q->where(function ($q) use ($prefix, $term, $positions, $byPosition) {
                        $q->orWhere($prefix.'people.first_name', 'like', "%$term%")
                            ->orWhere($prefix.'people.last_name', 'like', "%$term%")
                            ->orWhereRaw('CONCAT('.$prefix."people.first_name, ' ', ".$prefix.'people.last_name) like ?', ["%$term%"]);

                        if ($positions) {
                            $q->orWhereHas('currentPositions', $byPosition);
                        }
                    });
                }
            })
            ->when($positionTitle !== '', fn (Builder $q) => $q->whereHas('currentPositions', fn (Builder $q) => $q->where('title', 'like', "%$positionTitle%")))
            ->when($this->user_id, fn (Builder $q) => $q->whereIn('user_owner_id', $this->user_id))
            ->when($this->label_id, fn (Builder $q) => $q->whereHas('labels', fn (Builder $q) => $q->whereIn(config('laravel-crm.db_table_prefix').'labels.id', $this->label_id)))
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(25);
    }

    public function delete($id)
    {
        if ($person = Person::find($id)) {
            $this->authorize('delete', $person);

            $person->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.person_deleted')));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.people.person-index', [
            'users' => $this->users,
            'labels' => $this->labels,
            'filterCount' => $this->filterCount(),
            'headers' => $this->headers(),
            'people' => $this->people(),
            'positionsEnabled' => $this->positionsEnabled(),
        ]);
    }
}
