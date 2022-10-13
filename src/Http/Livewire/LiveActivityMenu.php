<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;

class LiveActivityMenu extends Component
{
    public function addNote()
    {
        $this->emit('addNoteActivity');
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
