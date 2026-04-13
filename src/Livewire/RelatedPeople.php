<?php

namespace VentureDrake\LaravelCrm\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Livewire\Traits\HasPersonSuggest;
use VentureDrake\LaravelCrm\Models\Person;

class RelatedPeople extends Component
{
    use HasPersonSuggest;
    use Toast;

    public $showAddRelatedPerson = false;

    public $model = null;

    public array $data = [];

    public $person_id;

    public $person_name;

    #[Computed, On('related-contacts-updated')]
    public function contacts()
    {
        return $this->model
            ->contacts()
            ->when(! empty($this->contactTypeFilter), function ($query) {
                return $query->leftJoin('contact_contact_type', 'contact_contact_type.contact_id', '=', 'contacts.id')
                    ->leftJoin('contact_types', 'contact_contact_type.contact_type_id', '=', 'contact_types.id')
                    ->where('contact_types.name', $this->contactTypeFilter);
            })
            ->where('entityable_type', 'LIKE', '%Person%')
            ->get();
    }

    public function add()
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

        $this->showAddRelatedPerson = false;
        $this->reset('person_id', 'person_name');
        $this->success('Related contact added');
        $this->dispatch('related-contacts-updated');
    }

    public function remove($index)
    {
        unset($this->data[$index]);
    }

    public function render()
    {
        return view('laravel-crm::livewire.related-people');
    }
}
