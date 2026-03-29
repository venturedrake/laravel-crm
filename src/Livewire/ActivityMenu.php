<?php

namespace VentureDrake\LaravelCrm\Livewire;

use Livewire\Component;

class ActivityMenu extends Component
{
    public function addNote()
    {
        $this->dispatch('select-activity-tab', 'notes');
    }

    public function addTask()
    {
        $this->dispatch('select-activity-tab', 'tasks');
    }

    public function addCall()
    {
        $this->dispatch('select-activity-tab', 'calls');
    }

    public function addMeeting()
    {
        $this->dispatch('select-activity-tab', 'meetings');
    }

    public function addLunch()
    {
        $this->dispatch('select-activity-tab', 'lunches');
    }

    public function addFile()
    {
        $this->dispatch('select-activity-tab', 'files');
    }

    public function render()
    {
        return view('laravel-crm::livewire.activity-menu');
    }
}
