<?php

namespace VentureDrake\LaravelCrm\Livewire;

use Illuminate\Support\Collection;
use Livewire\Component;

class KanbanBoard extends Component
{
    public $model;

    public $sortable;

    public $sortableBetweenStages;

    public function mount(
        $sortable = true,
        $sortableBetweenStages = true
    ) {
        $this->sortable = $sortable ?? false;
        $this->sortableBetweenStages = $sortableBetweenStages ?? false;
    }

    public function stages(): Collection
    {
        return collect();
    }

    public function records(): Collection
    {
        return collect();
    }

    public function isRecordInStage($record, $stage)
    {
        return $record['stage'] == $stage['id'];
    }

    public function onStageSorted($orderedIds)
    {
        //
    }

    public function onStageChanged($recordId, $stageId, $fromOrderedIds, $toOrderedIds)
    {
        //
    }

    /**
     * Re-render after a failed drag. Not $refresh: on Livewire 3 that adds no call to a
     * request already queued, so the call in there could still skip the render.
     */
    public function refreshBoard(): void
    {
        //
    }

    public function firstStageId()
    {
        $first = $this->stages()->first();

        if (! $first) {
            return null;
        }

        return is_array($first) ? ($first['id'] ?? null) : ($first->id ?? null);
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

        return view('laravel-crm::livewire.kanban-board', [
            'records' => $records,
            'stages' => $stages,
        ]);
    }
}
