<?php

namespace VentureDrake\LaravelCrm\Livewire;

use Livewire\Component;

class ActivityTabs extends Component
{
    public $activeTab = 'activity';

    public function render()
    {
        return view('laravel-crm::livewire.activity-tabs');
    }
}
