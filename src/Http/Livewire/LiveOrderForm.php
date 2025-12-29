<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Client;
use VentureDrake\LaravelCrm\Models\Organisation;
use VentureDrake\LaravelCrm\Models\Person;

class LiveOrderForm extends Component
{
    public $client_id;

    public $clientHasOrganizations = false;

    public $clientHasPeople = false;

    public $client_name;

    public $people = [];

    public $person_id;

    public $person_name;

    public $organizations = [];

    public $organization_id;

    public $organization_name;

    public function mount($order, $client = null, $organization = null, $person = null)
    {
        $this->client_id = old('client_id') ?? $order->client->id ?? $client->id ?? null;
        $this->client_name = old('client_name') ?? $order->client->name ?? $client->name ?? null;
        $this->person_id = old('person_id') ?? $order->person->id ?? $person->id ?? null;
        $this->person_name = old('person_name') ?? $order->person->name ?? $person->name ?? null;
        $this->organization_id = old('organization_id') ?? $order->organization->id ?? $organization->id ?? null;
        $this->organization_name = old('organization_name') ?? $order->organization->name ?? $organization->name ?? null;

        if ($this->client_id) {
            $this->getClientOrganizations();

            $this->getClientPeople();
        }
    }

    public function updatedClientId($value)
    {
        if ($client = Client::find($value)) {
            $this->client_name = $client->name;
        }
    }

    public function updatedClientName($value)
    {
        if ($this->client_id) {
            $this->getClientOrganizations();

            $this->getClientPeople();
        } else {
            $this->clientHasOrganizations = false;

            $this->clientHasPeople = false;

            $this->dispatchBrowserEvent('clientNameUpdated');
        }
    }

    public function updatedOrganizationId($value)
    {
        if ($organisation = Organisation::find($value)) {
            $this->organisation_name = $organisation->name;

            $this->emit('orderOrganisationSelected', [
                'id' => $this->organisation_id
            ]);
        }
    }

    public function updatedPersonId($value)
    {
        if ($person = Person::find($value)) {
            $this->person_name = $person->name;

            $this->emit('orderPersonSelected', [
                'id' => $this->person_id,
            ]);
        }
    }

    public function getClientOrganizations()
    {
        foreach (Customer::find($this->client_id)->contacts()
            ->where('entityable_type', 'LIKE', '%Organization%')
            ->get() as $contact) {
            $this->organizations[$contact->entityable_id] = $contact->entityable->name;
            $this->clientHasOrganizations = true;
        }
    }

    public function getClientPeople()
    {
        foreach (Customer::find($this->client_id)->contacts()
            ->where('entityable_type', 'LIKE', '%Person%')
            ->get() as $contact) {
            $this->people[$contact->entityable_id] = $contact->entityable->name;
            $this->clientHasPeople = true;
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.order-form');
    }
}
