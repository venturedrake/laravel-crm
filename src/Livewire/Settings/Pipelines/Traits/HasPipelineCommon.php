<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\Pipelines\Traits;

use Mary\Traits\Toast;

trait HasPipelineCommon
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
