<?php

namespace VentureDrake\LaravelCrm\Http\Livewire\Fields;

use Livewire\Component;

class CreateOrEdit extends Component
{
    public $type;
    
    public $options = [];
    
    public $fieldGroup;

    public $name;

    public $default;
    
    public $required = false;
    
    protected $rules = [
        'type' => 'required',
        'name' => 'required|max:255',
    ];
    
    public function mount()
    {
        // 
    }

    public function submit()
    {
        $this->validate();

        /*Contact::create([
            'name' => $this->name,
            'email' => $this->email,
        ]);*/
    }
    
    public function render()
    {
        return view('laravel-crm::livewire.fields.create-or-edit');
    }
}
