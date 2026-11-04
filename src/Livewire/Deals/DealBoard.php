<?php

namespace VentureDrake\LaravelCrm\Livewire\Deals;

use Carbon\Carbon;
use VentureDrake\LaravelCrm\Livewire\PipelineBoard;
use VentureDrake\LaravelCrm\Models\Deal;

class DealBoard extends PipelineBoard
{
    public $model = 'deal';

    #[Url]
    public string $search = '';

    #[Url]
    public ?array $user_id = [];

    #[Url]
    public ?array $label_id = [];

    public bool $showFilters = false;

    protected function boardModel(): string
    {
        return Deal::class;
    }

    protected function cardColumns(): array
    {
        return ['id', 'deal_id', 'title', 'amount', 'currency', 'closed_at'];
    }

    public function won($id)
    {
        if ($deal = Deal::find($id)) {
            $this->authorize('update', $deal);

            $deal->update([
                'closed_status' => 'won',
                'closed_at' => Carbon::now(),
                'pipeline_stage_id' => $this->stageIdNamed('Closed Won'),
            ]);

            $this->success(ucfirst(trans('laravel-crm::lang.deal_won')));
        }
    }

    public function lost($id)
    {
        if ($deal = Deal::find($id)) {
            $this->authorize('update', $deal);

            $deal->update([
                'closed_status' => 'lost',
                'closed_at' => Carbon::now(),
                'pipeline_stage_id' => $this->stageIdNamed('Closed Lost'),
            ]);

            $this->success(ucfirst(trans('laravel-crm::lang.deal_lost')));
        }
    }

    public function reopen($id)
    {
        if ($deal = Deal::find($id)) {
            $this->authorize('update', $deal);

            $deal->update([
                'closed_status' => null,
                'closed_at' => null,
                'pipeline_stage_id' => $this->stageIdNamed('Pending'),
            ]);

            $this->success(ucfirst(trans('laravel-crm::lang.deal_reopened')));
        }
    }
}
