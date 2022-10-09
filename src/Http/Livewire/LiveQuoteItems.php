<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class LiveQuoteItems extends Component
{
    use NotifyToast;
    
    public $quote;
    
    public function mount($quote)
    {
        $this->quote = $quote;
    }
    
    public function render()
    {
        return view('laravel-crm::livewire.quote-items');
    }
}
