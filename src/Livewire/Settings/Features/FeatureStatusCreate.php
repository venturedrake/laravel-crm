<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\Features;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Settings\Features\Traits\HasFeatureStatusCommon;
use VentureDrake\LaravelCrm\Models\Feature;
use VentureDrake\LaravelCrm\Models\FeatureStatus;

class FeatureStatusCreate extends Component
{
    use AuthorizesRequests, HasFeatureStatusCommon;

    public function mount()
    {
        $this->order = (FeatureStatus::max('order') ?? 0) + 1;
    }

    public function save()
    {
        $this->authorize('manageStatuses', Feature::class);

        $this->validate();

        if ($this->is_default) {
            FeatureStatus::where('is_default', true)->update(['is_default' => false]);
        }

        FeatureStatus::create([
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'order' => $this->order,
            'is_default' => $this->is_default,
            'is_closed' => $this->is_closed,
        ]);

        $this->success(
            ucfirst(trans('laravel-crm::lang.created')),
            redirectTo: route('laravel-crm.feature-statuses.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.feature-statuses.feature-status-create');
    }
}
