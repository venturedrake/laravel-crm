<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;

class LiveNote extends Component
{
    public $notes;
    public $content;

    protected $rules = [
        'content' => 'string|required',
    ];

    public function mount($notes)
    {
        $this->notes = $notes;
    }

    public function submit()
    {
        $note = $this->validate();
        
        //

        $this->clearFields();
    }

    private function clearFields()
    {
        $this->content = '';
    }
    
    public function render()
    {
        return view('laravel-crm::livewire.notes');
    }
}
