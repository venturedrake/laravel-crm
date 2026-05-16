<?php

namespace VentureDrake\LaravelCrm\Livewire\Quotes;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Livewire\Traits\SearchesEncryptableContacts;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Traits\ClearsProperties;
use VentureDrake\LaravelCrm\Traits\ResetsPaginationWhenPropsChanges;

class QuoteIndex extends Component
{
    use ClearsProperties, ResetsPaginationWhenPropsChanges, SearchesEncryptableContacts, Toast, WithPagination;

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

    public ?Pipeline $pipeline = null;

    public function mount()
    {
        $this->pipeline = Pipeline::where('model', get_class(new Quote))->first();
    }

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
            ['key' => 'created_at', 'label' => ucfirst(__('laravel-crm::lang.created')), 'format' => fn ($row, $field) => $field->diffForHumans()],
            ['key' => 'quote_id', 'label' => ucfirst(__('laravel-crm::lang.number'))],
            ['key' => 'reference', 'label' => ucfirst(__('laravel-crm::lang.reference'))],
            ['key' => 'title', 'label' => ucfirst(__('laravel-crm::lang.title'))],
            ['key' => 'labels', 'label' => ucfirst(__('laravel-crm::lang.labels')), 'sortable' => false, 'format' => fn ($row, $field) => $field],
            ['key' => 'person.name', 'label' => ucfirst(__('laravel-crm::lang.contact')), 'sortable' => false],
            ['key' => 'organization.name', 'label' => ucfirst(__('laravel-crm::lang.organization')), 'sortable' => false],
            ['key' => 'total', 'label' => ucfirst(__('laravel-crm::lang.total')), 'format' => fn ($row, $field) => money($field, $row->currency)],
            ['key' => 'issue_at', 'label' => ucwords(__('laravel-crm::lang.issue_date')), 'format' => fn ($row, $field) => ($field) ? $field->format(app('laravel-crm.settings')->get('date_format', 'Y-m-d')) : null],
            ['key' => 'expire_at', 'label' => ucwords(__('laravel-crm::lang.expiry_date')), 'format' => fn ($row, $field) => ($field) ? $field->format(app('laravel-crm.settings')->get('date_format', 'Y-m-d')) : null],
            /*  ['key' => 'title', 'label' => ucfirst(__('laravel-crm::lang.title'))],
            ['key' => 'labels', 'label' => ucfirst(__('laravel-crm::lang.labels')), 'format' => fn ($row, $field) => $field],
            ['key' => 'amount', 'label' => ucfirst(__('laravel-crm::lang.value')), 'format' => fn ($row, $field) => money($field, $row->currency)],*/
            /* ['key' => 'person.name', 'label' => ucfirst(__('laravel-crm::lang.contact'))],
            ['key' => 'organization.name', 'label' => ucfirst(__('laravel-crm::lang.organization'))],*/
            ['key' => 'pipeline_stage', 'label' => ucfirst(__('laravel-crm::lang.stage')), 'sortable' => false],
            ['key' => 'ownerUser.name', 'label' => 'Owner', 'sortable' => false, 'format' => fn ($row, $field) => $field ?? ucfirst(__('laravel-crm::lang.unallocated'))],
        ];
    }

    public function quotes(): LengthAwarePaginator
    {
        $allowedSortColumns = ['created_at', 'quote_id', 'reference', 'title', 'total', 'issue_at', 'expire_at'];
        $sortColumn = in_array($this->sortBy['column'], $allowedSortColumns) ? $this->sortBy['column'] : 'created_at';
        $sortDirection = $this->sortBy['direction'] ?? 'desc';

        return Quote::select(
            config('laravel-crm.db_table_prefix').'quotes.*',
            config('laravel-crm.db_table_prefix').'people.first_name',
            config('laravel-crm.db_table_prefix').'people.last_name',
            config('laravel-crm.db_table_prefix').'organizations.name'
        )
            ->leftJoin(config('laravel-crm.db_table_prefix').'people', config('laravel-crm.db_table_prefix').'quotes.person_id', '=', config('laravel-crm.db_table_prefix').'people.id')
            ->leftJoin(config('laravel-crm.db_table_prefix').'organizations', config('laravel-crm.db_table_prefix').'quotes.organization_id', '=', config('laravel-crm.db_table_prefix').'organizations.id')
            ->when($this->search, function (Builder $q) {
                $prefix = config('laravel-crm.db_table_prefix');
                $term = $this->search;

                $q->where(function ($q) use ($prefix, $term) {
                    $q->orWhere($prefix.'quotes.title', 'like', "%$term%");

                    if ($this->encryptionEnabled()) {
                        if (($personIds = $this->matchingPersonIds($term))->isNotEmpty()) {
                            $q->orWhereIn($prefix.'quotes.person_id', $personIds);
                        }
                        if (($organizationIds = $this->matchingOrganizationIds($term))->isNotEmpty()) {
                            $q->orWhereIn($prefix.'quotes.organization_id', $organizationIds);
                        }
                    } else {
                        $q->orWhere($prefix.'organizations.name', 'like', "%$term%")
                            ->orWhere($prefix.'people.first_name', 'like', "%$term%")
                            ->orWhere($prefix.'people.last_name', 'like', "%$term%")
                            ->orWhereRaw('CONCAT('.$prefix."people.first_name, ' ', ".$prefix.'people.last_name) like ?', ["%$term%"]);
                    }
                });
            })
            ->when($this->user_id, fn (Builder $q) => $q->whereIn('user_owner_id', $this->user_id))
            ->when($this->label_id, fn (Builder $q) => $q->whereHas('labels', fn (Builder $q) => $q->whereIn('labels.id', $this->label_id)))
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(25);
    }

    public function delete($id)
    {
        if ($quote = Quote::find($id)) {
            $quote->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.quote_deleted')));
        }
    }

    public function accept($id): void
    {
        if ($quote = Quote::find($id)) {
            $quote->update([
                'accepted_at' => Carbon::now(),
                'pipeline_stage_id' => $this->pipeline->pipelineStages()->where('name', 'Accepted')->first()->id ?? null,
            ]);

            $this->success(ucfirst(trans('laravel-crm::lang.quote_accepted')));
        }
    }

    public function reject($id): void
    {
        if ($quote = Quote::find($id)) {
            $quote->update([
                'rejected_at' => Carbon::now(),
                'pipeline_stage_id' => $this->pipeline->pipelineStages()->where('name', 'Rejected')->first()->id ?? null,
            ]);

            $this->success(ucfirst(trans('laravel-crm::lang.quote_rejected')));
        }
    }

    public function unaccept($id): void
    {
        if ($quote = Quote::find($id)) {
            $quote->update([
                'accepted_at' => null,
                'pipeline_stage_id' => $this->pipeline->pipelineStages()->where('name', 'Draft')->first()->id ?? null,
            ]);

            $this->success(ucfirst(trans('laravel-crm::lang.quote_unaccepted')));
        }
    }

    public function unreject($id): void
    {
        if ($quote = Quote::find($id)) {
            $quote->update([
                'rejected_at' => null,
                'pipeline_stage_id' => $this->pipeline->pipelineStages()->where('name', 'Draft')->first()->id ?? null,
            ]);

            $this->success(ucfirst(trans('laravel-crm::lang.quote_unrejected')));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.quotes.quote-index', [
            'users' => $this->users(),
            'labels' => $this->labels(),
            'filterCount' => $this->filterCount(),
            'headers' => $this->headers(),
            'quotes' => $this->quotes(),
        ]);
    }
}
