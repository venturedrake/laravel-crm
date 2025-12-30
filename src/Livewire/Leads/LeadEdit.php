<?php

namespace VentureDrake\LaravelCrm\Livewire\Leads;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Leads\Traits\HasLeadCommon;
use VentureDrake\LaravelCrm\Livewire\Traits\HasOrganizationSuggest;
use VentureDrake\LaravelCrm\Livewire\Traits\HasPersonSuggest;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Pipeline;

class LeadEdit extends Component
{
    use HasLeadCommon;
    use HasOrganizationSuggest;
    use HasPersonSuggest;

    public function mount(Lead $lead)
    {
        $this->lead = $lead;
        $this->organization_id = $lead->organization ? $lead->organization->id : null;
        $this->organization_name = $lead->organization ? $lead->organization->name : null;
        $this->person_id = $lead->person ? $lead->person->id : null;
        $this->person_name = $lead->person ? $lead->person->name : null;

        /*if ($address = $lead->getPrimaryAddress()) {
            $this->address_line_1 = $address->line_1;
            $this->address_line_2 = $address->line_2;
            $this->address_line_3 = $address->line_3;
            $this->address_suburb = $address->suburb;
            $this->address_state = $address->state;
            $this->address_postcode = $address->postcode;
            $this->address_country = $address->country;
        }

        if ($email = $lead->getPrimaryEmail()) {
            $this->email = $email->address;
            $this->email_type = $email->type;
        }

        if ($phone = $lead->getPrimaryPhone()) {
            $this->phone = $phone->number;
            $this->phone_type = $phone->type;
        }*/

        $this->title = $lead->title;
        $this->description = $lead->description;
        $this->amount = $lead->amount;
        $this->currency = $lead->currency;
        $this->pipeline = Pipeline::where('model', get_class(new Lead))->first();
        $this->pipeline_stage_id = $lead->pipelineStage->id ?? null;
        $this->labels = $lead->labels->pluck('id')->toArray();
        $this->user_owner_id = $lead->userOwner->id ?? null;
    }

    public function save()
    {
        $this->success(ucfirst(trans('laravel-crm::lang.lead_updated_successfully')));
    }

    public function render()
    {
        return view('laravel-crm::livewire.leads.lead-edit');
    }
}
