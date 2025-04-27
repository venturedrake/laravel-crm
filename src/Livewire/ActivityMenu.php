<?php

namespace VentureDrake\LaravelCrm\Livewire;

use Livewire\Component;

class ActivityMenu extends Component
{
    public function addNote()
    {
        $this->dispatch('addNoteActivity');
    }

    public function addTask()
    {
        $this->dispatch('addTaskActivity');
    }

    public function addCall()
    {
        $this->dispatch('addCallActivity');
    }

    public function addMeeting()
    {
        $this->dispatch('addMeetingActivity');
    }

    public function addLunch()
    {
        $this->dispatch('addLunchActivity');
    }

    public function addFile()
    {
        $this->dispatch('addFileActivity');
    }

    public function render()
    {
        return view('laravel-crm::livewire.activity-menu');
    }
}
