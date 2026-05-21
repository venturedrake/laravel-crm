<?php

namespace VentureDrake\LaravelCrm\Livewire\Features;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Features\Traits\HasFeatureCommon;
use VentureDrake\LaravelCrm\Models\Feature;

class FeatureEdit extends Component
{
    use HasFeatureCommon;

    public ?Feature $feature = null;

    public function mount(Feature $feature)
    {
        $this->feature = $feature;
        $this->title = $feature->title;
        $this->description = $feature->description;
        $this->is_public = (bool) $feature->is_public;
        $this->feature_status_id = $feature->feature_status_id;
    }

    public function save()
    {
        $this->validate();

        $this->featureService->update($this->feature, [
            'title' => $this->title,
            'description' => $this->description,
            'is_public' => $this->is_public,
            'feature_status_id' => $this->feature_status_id,
        ]);

        $this->success(
            ucfirst(trans('laravel-crm::lang.updated')),
            redirectTo: route('laravel-crm.features.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.features.feature-edit', [
            'statuses' => $this->statusOptions(),
        ]);
    }
}
