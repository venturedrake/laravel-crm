<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Person;

class LiveRelatedContactPerson extends Component
{
    public $model;
    public $contacts;
    public $person_id;
    public $person_name;
    public $actions;

    public function mount($model, $actions = true)
    {
        $this->model = $model;
        $this->actions = $actions;
        $this->getContacts();
    }

    public function link()
    {
        $data = $this->validate([
            'person_name' => 'required',
        ]);

        if ($this->person_id) {
            $person = Person::find($this->person_id);
        } else {
            $name = \VentureDrake\LaravelCrm\Http\Helpers\PersonName\firstLastFromName($data['person_name']);

            $person = Person::create([
                'first_name' => $name['first_name'],
                'last_name' => $name['last_name'] ?? null,
                'user_owner_id' => auth()->user()->id,
            ]);
        }

        $this->model->contacts()->create([
            'entityable_type' => $person->getMorphClass(),
            'entityable_id' => $person->id,
        ]);

        $person->contacts()->create([
            'entityable_type' => $this->model->getMorphClass(),
            'entityable_id' => $this->model->id,
        ]);

        $this->resetFields();

        $this->getContacts();

        $this->dispatchBrowserEvent('linkedPerson');
    }

    public function remove($id)
    {
        if ($person = Person::find($id)) {
            $this->model->contacts()
                ->where([
                    'entityable_type' => $person->getMorphClass(),
                    'entityable_id' => $person->id,
                ])
                ->delete();

            $person->contacts()
                ->where([
                    'entityable_type' => $this->model->getMorphClass(),
                    'entityable_id' => $this->model->id,
                ])
                ->delete();
        }

        $this->getContacts();

        $this->dispatchBrowserEvent('linkedPerson');
    }

    public function updatedPersonName($value)
    {
        $this->dispatchBrowserEvent('updatedNameFieldAutocomplete');
    }

    private function getContacts()
    {
        $this->contacts = $this->model
            ->contacts()
            ->where('entityable_type', 'LIKE', '%Person%')
            ->get();
    }

    private function resetFields()
    {
        $this->reset('person_id', 'person_name');
    }

    public function render()
    {
        return view('laravel-crm::livewire.related-contact-people');
    }
}
