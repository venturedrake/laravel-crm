<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\PipelineStages\Traits;

use Mary\Traits\Toast;

trait HasPipelineStageCommon
{
    use Toast;

    public $name;

    protected function rules()
    {
        return [
            'name' => 'required|max:255',
        ];
    }
}
