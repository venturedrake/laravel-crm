<?php

namespace VentureDrake\LaravelCrm\Livewire\Deals;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use VentureDrake\LaravelCrm\Livewire\KanbanBoard;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Pipeline;

class DealBoard extends KanbanBoard
{
    public $layout = 'board';

    public $model = 'deal';

    #[Url]
    public string $search = '';

    #[Url]
    public ?array $user_id = [];

    #[Url]
    public ?array $label_id = [];

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
        $deals = Deal::when($this->search, fn (Builder $q) => $q->where('title', 'like', "%$this->search%"))
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

    public function render()
    {
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

        return view('laravel-crm::livewire.deals.deal-board', [
            'users' => $this->users(),
            'labels' => $this->labels(),
            'filterCount' => $this->filterCount(),
            'records' => $records,
            'stages' => $stages,
        ]);
    }
}
