<?php

namespace VentureDrake\LaravelCrm\Livewire\People;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Person;

class PersonShow extends Component
{
    public Person $person;

    public function render()
    {
        return view('laravel-crm::livewire.people.person-show');
    }
}
