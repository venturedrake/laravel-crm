<?php

namespace VentureDrake\LaravelCrm\Livewire\Deals;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Livewire\KanbanBoard;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Pipeline;

class DealBoard extends KanbanBoard
{
    use Toast;

    public $layout = 'board';

    public $model = 'deal';

    #[Url]
    public string $search = '';

    #[Url]
    public ?array $user_id = [];

    #[Url]
    public ?array $label_id = [];

    public bool $showFilters = false;

    public ?Pipeline $pipeline = null;

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

    public function stages(): Collection
    {
        if ($pipeline = Pipeline::where('model', get_class(new Deal))->first()) {
            return $pipeline->pipelineStages()
                ->orderBy('order')
                ->orderBy('id')
                ->get();
        }
    }

    public function onStageSorted($orderedIds)
    {
        foreach ($orderedIds as $orderNumber => $dealId) {
            Deal::find($dealId)->update([
                'pipeline_stage_order' => $orderNumber + 1,
            ]);
        }
    }

    public function onStageChanged($recordId, $stageId, $fromOrderedIds, $toOrderedIds)
    {
        Deal::find($recordId)->update([
            'pipeline_stage_id' => $stageId,
        ]);

        foreach ($fromOrderedIds as $orderNumber => $leadId) {
            Deal::find($leadId)->update([
                'pipeline_stage_order' => $orderNumber + 1,
            ]);
        }

        foreach ($toOrderedIds as $orderNumber => $leadId) {
            Deal::find($leadId)->update([
                'pipeline_stage_order' => $orderNumber + 1,
            ]);
        }
    }

    public function records(): Collection
    {
        $deals = Deal::select(
            config('laravel-crm.db_table_prefix').'deals.*',
            config('laravel-crm.db_table_prefix').'people.first_name',
            config('laravel-crm.db_table_prefix').'people.last_name',
            config('laravel-crm.db_table_prefix').'organizations.name'
        )
            ->leftJoin(config('laravel-crm.db_table_prefix').'people', config('laravel-crm.db_table_prefix').'deals.person_id', '=', config('laravel-crm.db_table_prefix').'people.id')
            ->leftJoin(config('laravel-crm.db_table_prefix').'organizations', config('laravel-crm.db_table_prefix').'deals.organization_id', '=', config('laravel-crm.db_table_prefix').'organizations.id')
            ->when($this->search, function (Builder $q) {
                $q->where(function ($q) {
                    $q->orWhere(config('laravel-crm.db_table_prefix').'deals.title', 'like', "%$this->search%")
                        ->orWhere(config('laravel-crm.db_table_prefix').'organizations.name', 'like', "%$this->search%")
                        ->orWhere(config('laravel-crm.db_table_prefix').'people.first_name', 'like', "%$this->search%")
                        ->orWhere(config('laravel-crm.db_table_prefix').'people.last_name', 'like', "%$this->search%")
                        ->orWhereRaw('CONCAT('.config('laravel-crm.db_table_prefix')."people.first_name, ' ', ".config('laravel-crm.db_table_prefix').'people.last_name) like ?', ["%$this->search%"]);
                });
            })
            ->when($this->user_id, fn (Builder $q) => $q->whereIn('user_owner_id', $this->user_id))
            ->when($this->label_id, fn (Builder $q) => $q->whereHas('labels', fn (Builder $q) => $q->whereIn('labels.id', $this->label_id)))
            ->orderBy('pipeline_stage_order')
            ->oldest()
            ->get();

        return $deals->map(function (Deal $deal) {
            return [
                'id' => $deal->id,
                'title' => $deal->title,
                'labels' => $deal->labels,
                'stage' => $deal->pipelineStage->id ?? $this->firstStageId(),
                'number' => $deal->deal_id,
                'amount' => $deal->amount,
                'currency' => $deal->currency,
            ];
        });
    }

    public function updatedSearch()
    {
        $this->render();
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

    public function render()
    {
        $this->pipeline = Pipeline::where('model', get_class(new Deal))->first();

        $stages = $this->stages();

        $records = $this->records();

        $stages = $stages
            ->map(function ($stage) use ($records) {
                $stage['group'] = $this->id();
                $stage['stageRecordsId'] = "{$this->id()}-{$stage['id']}";
                $stage['records'] = $records
                    ->filter(function ($record) use ($stage) {
                        return $this->isRecordInStage($record, $stage);
                    });

                return $stage;
            });

        $this->dispatch('board-loaded');

        return view('laravel-crm::livewire.deals.deal-board', [
            'users' => $this->users(),
            'labels' => $this->labels(),
            'filterCount' => $this->filterCount(),
            'records' => $records,
            'stages' => $stages,
        ]);
    }
}
