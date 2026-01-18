<?php

namespace VentureDrake\LaravelCrm\Livewire\People;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Person;

class PersonShow extends Component
{
    public Person $person;

    public function delete($id)
    {
        if ($person = Person::find($id)) {
            $person->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.person_deleted')), redirectTo: route('laravel-crm.people.index'));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.people.person-show');
    }
}
