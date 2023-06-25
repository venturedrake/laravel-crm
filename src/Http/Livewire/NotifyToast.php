<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;

class NotifyToast extends Component
{
    public $level = 'success';
    public $message;

    public function render()
    {
        return view('laravel-crm::livewire.notify-toast');
    }
}
