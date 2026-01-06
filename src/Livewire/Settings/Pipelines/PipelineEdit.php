<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\Pipelines;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Settings\Pipelines\Traits\HasPipelineCommon;
use VentureDrake\LaravelCrm\Models\Pipeline;

class PipelineEdit extends Component
{
    use HasPipelineCommon;

    public ?Pipeline $pipeline = null;

    public function mount()
    {
        $this->name = $this->pipeline->name;
    }

    public function save()
    {
        $this->validate();

        $this->pipeline->update([
            'name' => $this->name,
        ]);

        $this->success(
            ucfirst(trans('laravel-crm::lang.pipeline_updated')),
            redirectTo: route('laravel-crm.pipelines.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.pipelines.pipeline-edit');
    }
}
