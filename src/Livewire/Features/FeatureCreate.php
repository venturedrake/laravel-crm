<?php

namespace VentureDrake\LaravelCrm\Livewire\Features;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Features\Traits\HasFeatureCommon;
use VentureDrake\LaravelCrm\Models\Feature;

class FeatureCreate extends Component
{
    use AuthorizesRequests, HasFeatureCommon;

    public function save()
    {
        $this->authorize('create', Feature::class);

        $this->validate();

        $this->featureService->create([
            'title' => $this->title,
            'description' => $this->description,
            'is_public' => $this->is_public,
            'feature_status_id' => $this->feature_status_id,
        ], auth()->user());

        $this->success(
            ucfirst(trans('laravel-crm::lang.feature_stored')),
            redirectTo: route('laravel-crm.features.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.features.feature-create', [
            'statuses' => $this->statusOptions(),
        ]);
    }
}
