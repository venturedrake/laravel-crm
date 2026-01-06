<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\PipelineStages;

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrm\Traits\ClearsProperties;
use VentureDrake\LaravelCrm\Traits\ResetsPaginationWhenPropsChanges;

class PipelineStageIndex extends Component
{
    use ClearsProperties, ResetsPaginationWhenPropsChanges, Toast, WithPagination;

    public $layout = 'index';

    #[Url]
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public function headers()
    {
        return [
            ['key' => 'name', 'label' => ucfirst(__('laravel-crm::lang.name'))],
            ['key' => 'pipeline.name', 'label' => ucfirst(__('laravel-crm::lang.attached_to')), 'relation' => 'pipeline'],
        ];
    }

    public function pipelineStages(): LengthAwarePaginator
    {
        return PipelineStage::orderBy(...array_values($this->sortBy))
            ->paginate(25);
    }

    public function delete($id)
    {
        if ($pipelineStage = PipelineStage::find($id)) {
            $pipelineStage->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.pipeline_stage_deleted')));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.pipeline-stages.pipeline-stage-index', [
            'headers' => $this->headers(),
            'pipelineStages' => $this->pipelineStages(),
        ]);
    }
}
