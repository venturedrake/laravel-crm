<?php

namespace VentureDrake\LaravelCrm\Livewire\Deals;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Traits\ClearsProperties;
use VentureDrake\LaravelCrm\Traits\ResetsPaginationWhenPropsChanges;

class DealIndex extends Component
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

    public ?Pipeline $pipeline = null;

    public function mount()
    {
        $this->pipeline = Pipeline::where('model', get_class(new Deal))->first();
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
            ['key' => 'deal_id', 'label' => ucfirst(__('laravel-crm::lang.number'))],
            ['key' => 'title', 'label' => ucfirst(__('laravel-crm::lang.title'))],
            ['key' => 'labels', 'label' => ucfirst(__('laravel-crm::lang.labels')), 'format' => fn ($row, $field) => $field],
            ['key' => 'amount', 'label' => ucfirst(__('laravel-crm::lang.value')), 'format' => fn ($row, $field) => money($field, $row->currency)],
            ['key' => 'person.name', 'label' => ucfirst(__('laravel-crm::lang.contact'))],
            ['key' => 'organization.name', 'label' => ucfirst(__('laravel-crm::lang.organization'))],
            ['key' => 'pipeline_stage', 'label' => ucfirst(__('laravel-crm::lang.stage'))],
            ['key' => 'expected_close', 'label' => ucwords(__('laravel-crm::lang.expected_close'))],
            ['key' => 'ownerUser.name', 'label' => 'Owner', 'format' => fn ($row, $field) => $field ?? ucfirst(__('laravel-crm::lang.unallocated'))],
        ];
    }

    public function rowDecoration()
    {
        return [
            'bg-success/20' => fn (Deal $deal) => $deal->closed_status == 'won',
            'bg-error/20' => fn (Deal $deal) => $deal->closed_status == 'lost',
        ];
    }

    public function deals(): LengthAwarePaginator
    {
        return Deal::when($this->search, fn (Builder $q) => $q->where('title', 'like', "%$this->search%"))
            ->when($this->user_id, fn (Builder $q) => $q->whereIn('user_owner_id', $this->user_id))
            ->when($this->label_id, fn (Builder $q) => $q->whereHas('labels', fn (Builder $q) => $q->whereIn('labels.id', $this->label_id)))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(25);
    }

    public function won($id)
    {
        if ($deal = Deal::find($id)) {
            $deal->update([
                'closed_status' => 'won',
                'closed_at' => Carbon::now(),
                'pipeline_stage_id' => $this->pipeline->pipelineStages()->where('name', 'Closed Won')->first()->id ?? null,
            ]);

            $this->success(ucfirst(trans('laravel-crm::lang.deal_won')));
        }
    }

    public function lost($id)
    {
        if ($deal = Deal::find($id)) {
            $deal->update([
                'closed_status' => 'lost',
                'closed_at' => Carbon::now(),
                'pipeline_stage_id' => $this->pipeline->pipelineStages()->where('name', 'Closed Lost')->first()->id ?? null,
            ]);

            $this->success(ucfirst(trans('laravel-crm::lang.deal_lost')));
        }
    }

    public function reopen($id)
    {
        if ($deal = Deal::find($id)) {
            $deal->update([
                'closed_status' => null,
                'closed_at' => null,
                'pipeline_stage_id' => $this->pipeline->pipelineStages()->where('name', 'Pending')->first()->id ?? null,
            ]);

            $this->success(ucfirst(trans('laravel-crm::lang.deal_reopened')));
        }
    }

    public function delete($id)
    {
        if ($deal = Deal::find($id)) {
            $deal->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.deal_deleted')));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.deals.deal-index', [
            'users' => $this->users(),
            'labels' => $this->labels(),
            'filterCount' => $this->filterCount(),
            'headers' => $this->headers(),
            'deals' => $this->deals(),
            'rowDecoration' => $this->rowDecoration(),
        ]);
    }
}
