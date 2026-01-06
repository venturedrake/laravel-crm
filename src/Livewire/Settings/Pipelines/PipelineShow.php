<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\Pipelines;

use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Pipeline;

class PipelineShow extends Component
{
    use Toast;

    public Pipeline $pipeline;

    public function render()
    {
        return view('laravel-crm::livewire.settings.pipelines.pipeline-show');
    }
}
