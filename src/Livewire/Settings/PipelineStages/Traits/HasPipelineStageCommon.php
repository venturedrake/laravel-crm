<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\PipelineStages\Traits;

use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Pipeline;

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

    /**
     * Names pipeline_id as the form's label does, rather than "pipeline id".
     */
    protected function validationAttributes()
    {
        return [
            'pipeline_id' => __('laravel-crm::lang.pipeline'),
        ];
    }

    public function mountCommon()
    {
        $this->pipelines = Pipeline::all();
    }
}
