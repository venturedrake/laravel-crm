<?php

namespace VentureDrake\LaravelCrm\Livewire\Features;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Feature;

class FeatureShow extends Component
{
    use AuthorizesRequests, Toast;

    public Feature $feature;

    public function mount(Feature $feature)
    {
        $this->feature = $feature;
    }

    public function delete($id)
    {
        if ($feature = Feature::find($id)) {
            $this->authorize('delete', $feature);

            $feature->delete();

            $this->success(
                ucfirst(trans('laravel-crm::lang.feature_deleted')),
                redirectTo: route('laravel-crm.features.index')
            );
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.features.feature-show');
    }
}
