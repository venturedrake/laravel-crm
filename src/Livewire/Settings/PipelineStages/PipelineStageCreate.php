<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\PipelineStages;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Settings\PipelineStages\Traits\HasPipelineStageCommon;
use VentureDrake\LaravelCrm\Models\PipelineStage;

class PipelineStageCreate extends Component
{
    use AuthorizesRequests;
    use HasPipelineStageCommon;

    public function mount()
    {
        $this->mountCommon();
    }

    public function save()
    {
        $this->authorize('create', PipelineStage::class);

        $this->validate();

        $pipelineStage = PipelineStage::create([
            'name' => $this->name,
            'description' => $this->description,
            'pipeline_id' => $this->pipeline_id,
            'order' => ((int) PipelineStage::where('pipeline_id', $this->pipeline_id)->max('order')) + 1,
        ]);

        $this->success(
            ucfirst(trans('laravel-crm::lang.pipeline_stage_stored')),
            redirectTo: route('laravel-crm.pipeline-stages.show', $pipelineStage)
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.pipeline-stages.pipeline-stage-create');
    }
}
