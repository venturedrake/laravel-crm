<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Person;

class LiveRelatedPerson extends Component
{
    public $model;
    public $people;
    public $person_id;
    public $person_name;
    public $actions;

    public function mount($model, $actions = true)
    {
        $this->model = $model;
        $this->actions = $actions;
        $this->getPeople();
    }

    public function link()
    {
        $data = $this->validate([
            'person_name' => 'required',
        ]);

        if ($this->person_id) {
            $person = Person::find($this->person_id);
            $person->update([
                'organisation_id' => $this->model->id,
            ]);
        } else {
            $name = \VentureDrake\LaravelCrm\Http\Helpers\PersonName\firstLastFromName($data['person_name']);

            $person = Person::create([
                'external_id' => Uuid::uuid4()->toString(),
                'first_name' => $name['first_name'],
                'last_name' => $name['last_name'] ?? null,
                'user_owner_id' => auth()->user()->id,
                'organisation_id' => $this->model->id,
            ]);
        }

        $this->resetFields();

        $this->getPeople();

        $this->dispatchBrowserEvent('linkedPerson');
    }

    public function remove($id)
    {
        if ($person = Person::find($id)) {
            $person->update([
                'organisation_id' => null,
            ]);
        }

        $this->getPeople();

        $this->dispatchBrowserEvent('linkedPerson');
    }

    public function updatedPersonName($value)
    {
        $this->dispatchBrowserEvent('updatedNameFieldAutocomplete');
    }

    private function getPeople()
    {
        $this->people = $this->model->people()->get();
    }

    private function resetFields()
    {
        $this->reset('person_id', 'person_name');
    }

    public function render()
    {
        return view('laravel-crm::livewire.related-people');
    }
}
