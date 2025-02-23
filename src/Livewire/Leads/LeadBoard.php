<?php

namespace VentureDrake\LaravelCrm\Livewire\Leads;

use Illuminate\Support\Collection;
use VentureDrake\LaravelCrm\Livewire\KanbanBoard;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Pipeline;

class LeadBoard extends KanbanBoard
{
    public $layout = 'board';

    public $model = 'lead';

    public $leads;

    public function stages(): Collection
    {
        if ($pipeline = Pipeline::where('model', get_class(new Lead))->first()) {
            return $pipeline->pipelineStages()
                ->orderBy('order')
                ->orderBy('id')
                ->get();
        }
    }

    public function onStageChanged($recordId, $stageId, $fromOrderedIds, $toOrderedIds)
    {
        Lead::find($recordId)->update([
            'pipeline_stage_id' => $stageId,
        ]);
    }

    public function records(): Collection
    {
        return $this->leads->map(function (Lead $lead) {
            return [
                'id' => $lead->id,
                'title' => $lead->title,
                'labels' => $lead->labels,
                'stage' => $lead->pipelineStage->id ?? $this->firstStageId(),
                'number' => $lead->lead_id,
                'amount' => $lead->amount,
                'currency' => $lead->currency,
            ];
        });
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

        return view('laravel-crm::livewire.leads.lead-board', [
            'records' => $records,
            'stages' => $stages,
        ]);
    }
}
