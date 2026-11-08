<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\LeadSources;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Settings\LeadSources\Traits\HasLeadSourceCommon;
use VentureDrake\LaravelCrm\Models\LeadSource;

class LeadSourceEdit extends Component
{
    use AuthorizesRequests;
    use HasLeadSourceCommon;

    public ?LeadSource $leadSource = null;

    public function mount()
    {
        $this->name = $this->leadSource->name;
        $this->description = $this->leadSource->description;
    }

    public function save()
    {
        $this->authorize('update', $this->leadSource);

        $this->validate();

        $this->leadSource->update([
            'name' => $this->name,
            'description' => $this->description,
        ]);

        $this->success(
            ucfirst(trans('laravel-crm::lang.lead_source_updated')),
            redirectTo: route('laravel-crm.lead-sources.show', $this->leadSource)
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.lead-sources.lead-source-edit');
    }
}
