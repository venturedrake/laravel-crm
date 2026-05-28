<?php

namespace VentureDrake\LaravelCrm\Livewire\Features;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Livewire\KanbanBoard;
use VentureDrake\LaravelCrm\Models\Feature;
use VentureDrake\LaravelCrm\Models\FeatureStatus;

class FeatureBoard extends KanbanBoard
{
    use AuthorizesRequests, Toast;

    public $layout = 'board';

    public $model = 'feature';

    #[Url]
    public string $search = '';

    public function stages(): Collection
    {
        return FeatureStatus::orderBy('order')
            ->orderBy('id')
            ->get();
    }

    public function records(): Collection
    {
        return Feature::query()
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (Feature $feature) {
                return [
                    'id' => $feature->id,
                    'title' => $feature->title,
                    'stage' => $feature->feature_status_id ?? $this->firstStageId(),
                    'number' => $feature->feature_id,
                    'votes_count' => $feature->votes_count,
                    'comments_count' => $feature->comments_count,
                ];
            });
    }

    public function onStageChanged($recordId, $stageId, $fromOrderedIds, $toOrderedIds)
    {
        $feature = Feature::findOrFail($recordId);

        $this->authorize('update', $feature);

        $feature->update(['feature_status_id' => $stageId]);
    }

    public function updatedSearch()
    {
        $this->render();
    }

    public function delete($id)
    {
        if ($feature = Feature::find($id)) {
            $this->authorize('delete', $feature);

            $feature->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.feature_deleted')));
        }
    }

    public function render()
    {
        $stages = $this->stages();
        $records = $this->records();

        $stages = $stages->map(function ($stage) use ($records) {
            $stage['group'] = $this->id();
            $stage['stageRecordsId'] = "{$this->id()}-{$stage['id']}";
            $stage['records'] = $records->filter(function ($record) use ($stage) {
                return $this->isRecordInStage($record, $stage);
            });

            return $stage;
        });

        $this->dispatch('board-loaded');

        return view('laravel-crm::livewire.features.feature-board', [
            'records' => $records,
            'stages' => $stages,
        ]);
    }
}
