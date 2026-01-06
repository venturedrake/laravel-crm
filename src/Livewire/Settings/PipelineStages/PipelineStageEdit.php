<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\PipelineStages;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Settings\PipelineStages\Traits\HasPipelineStageCommon;
use VentureDrake\LaravelCrm\Models\PipelineStage;

class PipelineStageEdit extends Component
{
    use HasPipelineStageCommon;

    public ?PipelineStage $pipelineStage = null;

    public function mount()
    {
        $this->mountCommon();
        $this->name = $this->pipelineStage->name;
        $this->description = $this->pipelineStage->description;
        $this->pipeline_id = $this->pipelineStage->pipeline_id;
    }

    public function save()
    {
        $this->validate();

        $this->pipelineStage->update([
            'name' => $this->name,
            'description' => $this->description,
            'pipeline_id' => $this->pipeline_id,
        ]);

        $this->success(
            ucfirst(trans('laravel-crm::lang.pipeline_stage_updated')),
            redirectTo: route('laravel-crm.pipeline-stages.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.pipeline-stages.pipeline-stage-edit');
    }
}
