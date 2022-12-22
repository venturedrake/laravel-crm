<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;

class LiveActivityMenu extends Component
{
    public function addNote()
    {
        $this->emit('addNoteActivity');
    }

    public function addTask()
    {
        $this->emit('addTaskActivity');
    }

    public function addCall()
    {
        $this->emit('addCallActivity');
    }

    public function addMeeting()
    {
        $this->emit('addMeetingActivity');
    }

    public function addLunch()
    {
        $this->emit('addLunchActivity');
    }

    public function addFile()
    {
        $this->emit('addFileActivity');
    }

    public function render()
    {
        return view('laravel-crm::livewire.activity-menu');
    }
}
