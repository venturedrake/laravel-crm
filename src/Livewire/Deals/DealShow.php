<?php

namespace VentureDrake\LaravelCrm\Livewire\Deals;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Deal;

class DealShow extends Component
{
    public $deal;

    public $email;

    public $phone;

    public $address;

    public function mount(Deal $deal)
    {
        $this->deal = $deal;
        /* $this->email = $lead->getPrimaryEmail();
         $this->phone = $lead->getPrimaryPhone();
         $this->address = $lead->getPrimaryAddress();*/
    }

    public function render()
    {
        return view('laravel-crm::livewire.deals.deal-show');
    }
}
