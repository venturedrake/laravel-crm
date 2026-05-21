<?php

namespace VentureDrake\LaravelCrm\Livewire\Features;

use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Feature;

class FeatureShow extends Component
{
    use Toast;

    public Feature $feature;

    public function mount(Feature $feature)
    {
        $this->feature = $feature;
    }

    public function delete($id)
    {
        if ($feature = Feature::find($id)) {
            $feature->delete();

            $this->success(
                ucfirst(trans('laravel-crm::lang.deleted')),
                redirectTo: route('laravel-crm.features.index')
            );
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.features.feature-show');
    }
}
