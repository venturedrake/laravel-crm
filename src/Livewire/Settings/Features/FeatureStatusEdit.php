<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\Features;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Settings\Features\Traits\HasFeatureStatusCommon;
use VentureDrake\LaravelCrm\Models\FeatureStatus;

class FeatureStatusEdit extends Component
{
    use HasFeatureStatusCommon;

    public ?FeatureStatus $featureStatus = null;

    public function mount(FeatureStatus $featureStatus)
    {
        $this->featureStatus = $featureStatus;
        $this->name = $featureStatus->name;
        $this->description = $featureStatus->description;
        $this->color = $featureStatus->color ?? '#6c757d';
        $this->order = $featureStatus->order;
        $this->is_default = (bool) $featureStatus->is_default;
        $this->is_closed = (bool) $featureStatus->is_closed;
    }

    public function save()
    {
        $this->validate();

        if ($this->is_default) {
            FeatureStatus::where('is_default', true)
                ->where('id', '!=', $this->featureStatus->id)
                ->update(['is_default' => false]);
        }

        $this->featureStatus->update([
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'order' => $this->order,
            'is_default' => $this->is_default,
            'is_closed' => $this->is_closed,
        ]);

        $this->success(
            ucfirst(trans('laravel-crm::lang.updated')),
            redirectTo: route('laravel-crm.feature-statuses.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.feature-statuses.feature-status-edit');
    }
}
