<?php

namespace VentureDrake\LaravelCrm\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class ActivityTabs extends Component
{
    public $model;

    public $activeTab = 'activity';

    #[On('select-activity-tab')]
    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('laravel-crm::livewire.activity-tabs');
    }
}
