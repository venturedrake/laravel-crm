<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\PipelineStages\Traits;

use Mary\Traits\Toast;

trait HasPipelineStageCommon
{
    use Toast;

    public $name;

    public $description;

    public $pipeline_id;

    public $pipelines;

    protected function rules()
    {
        return [
            'name' => 'required|max:255',
            'pipeline_id' => 'required',
        ];
    }

    public function mountCommon()
    {
        $this->pipelines = \VentureDrake\LaravelCrm\Models\Pipeline::all();
    }
}
