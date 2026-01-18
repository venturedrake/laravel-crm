<?php

namespace VentureDrake\LaravelCrm\Livewire\People;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\People\Traits\HasPersonCommon;
use VentureDrake\LaravelCrm\Models\Person;

class PersonEdit extends Component
{
    use HasPersonCommon;

    public Person $person;

    public function mount()
    {
        $this->mountCommon();

        $this->title = $this->person->title;
        $this->first_name = $this->person->first_name;
        $this->last_name = $this->person->last_name;
        $this->middle_name = $this->person->middle_name;
        $this->gender = $this->person->gender;
        $this->birthday = $this->person->birthday;
        $this->description = $this->person->description;
        $this->labels = $this->person->labels()->pluck('id')->toArray();
        $this->user_owner_id = $this->person->user_owner_id;

        if ($this->person->phones->count() == 0) {
            $this->addPhone();
        } else {
            foreach ($this->person->phones as $phone) {
                $this->phones[] = [
                    'id' => $phone->id,
                    'type' => $phone->type,
                    'number' => $phone->number,
                    'primary' => $phone->primary,
                ];
            }
        }

        if ($this->person->emails->count() == 0) {
            $this->addEmail();
        } else {
            foreach ($this->person->emails as $email) {
                $this->emails[] = [
                    'id' => $email->id,
                    'type' => $email->type,
                    'address' => $email->address,
                    'primary' => $email->primary,
                ];
            }
        }

        if ($this->person->addresses->count() == 0) {
            $this->addAddress();
        } else {
            foreach ($this->person->addresses as $address) {
                $this->addresses[] = [
                    'id' => $address->id,
                    'type' => $address->address_type_id,
                    'primary' => $address->primary,
                    'name' => $address->name,
                    'contact' => $address->contact,
                    'phone' => $address->phone,
                    'address' => $address->address,
                    'line1' => $address->line1,
                    'line2' => $address->line2,
                    'line3' => $address->line3,
                    'city' => $address->city,
                    'state' => $address->state,
                    'code' => $address->code,
                    'country' => $address->country,
                ];
            }
        }
    }

    public function save()
    {
        $this->validate();

        // Create a request object to pass to services
        $request = \VentureDrake\LaravelCrm\Http\Helpers\PublicProperties\asRequest($this);

        $this->personService->update($this->person, $request);

        $this->person->labels()->sync($request->labels ?? []);

        $this->success(
            ucfirst(trans('laravel-crm::lang.person_updated')),
            redirectTo: route('laravel-crm.people.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.people.person-edit');
    }
}
