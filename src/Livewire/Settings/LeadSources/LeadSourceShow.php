<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\LeadSources;

use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\LeadSource;

class LeadSourceShow extends Component
{
    use Toast;

    public LeadSource $leadSource;

    public function delete($id)
    {
        if ($leadSource = LeadSource::find($id)) {
            $leadSource->delete();

            $this->success(
                ucfirst(trans('laravel-crm::lang.lead_source_deleted')),
                redirectTo: route('laravel-crm.lead-sources.index')
            );
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.lead-sources.lead-source-show');
    }
}
