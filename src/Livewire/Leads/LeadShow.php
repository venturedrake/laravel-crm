<?php

namespace VentureDrake\LaravelCrm\Livewire\Leads;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Lead;

class LeadShow extends Component
{
    public $lead;

    public $email;

    public $phone;

    public $address;

    public function mount(Lead $lead)
    {
        $this->lead = $lead;
        $this->email = $lead->getPrimaryEmail();
        $this->phone = $lead->getPrimaryPhone();
        $this->address = $lead->getPrimaryAddress();
    }

    public function render()
    {
        return view('laravel-crm::livewire.leads.lead-show');
    }
}
