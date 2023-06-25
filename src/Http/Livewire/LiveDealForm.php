<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Client;
use VentureDrake\LaravelCrm\Models\Organisation;
use VentureDrake\LaravelCrm\Models\Person;

class LiveDealForm extends Component
{
    public $client_id;
    public $clientHasOrganisations = false;
    public $clientHasPeople = false;
    public $client_name;
    public $people = [];
    public $person_id;
    public $person_name;
    public $organisations = [];
    public $organisation_id;
    public $organisation_name;
    public $title;
    public $generateTitle;

    public function mount($deal, $generateTitle = true, $client = null, $organisation = null, $person = null)
    {
        $this->client_id = old('client_id') ?? $deal->client->id ?? $client->id ?? null;
        $this->client_name = old('client_name') ?? $deal->client->name ?? $client->name ?? null;
        $this->person_id = old('person_id') ?? $deal->person->id ?? $person->id ?? null;
        $this->person_name = old('person_name') ?? $deal->person->name ?? $person->name ?? null;
        $this->organisation_id = old('organisation_id') ?? $deal->organisation->id ?? $organisation->id ?? null;
        $this->organisation_name = old('organisation_name') ?? $deal->organisation->name ?? $organisation->name ?? null;

        if ($this->client_id) {
            $this->getClientOrganisations();

            $this->getClientPeople();
        }

        $this->title = old('title') ?? $deal->title ?? null;
        $this->generateTitle = $generateTitle;

        if (old('title') || (isset($deal) && $deal->title)) {
            $this->generateTitle = false;
        } else {
            $this->generateTitle();
        }
    }

    public function updatedClientName($value)
    {
        $this->generateTitle();

        if ($this->client_id) {
            $this->getClientOrganisations();

            $this->getClientPeople();
        } else {
            $this->clientHasOrganisations = false;

            $this->clientHasPeople = false;

            $this->dispatchBrowserEvent('clientNameUpdated');

            if (! $this->organisation_id) {
                $this->dispatchBrowserEvent('selectedOrganisation');
            }

            if (! $this->person_id) {
                $this->dispatchBrowserEvent('selectedPerson');
            }
        }
    }

    public function updatedOrganisationId($value)
    {
        if ($organisation = Organisation::find($value)) {
            $address = $organisation->getPrimaryAddress();
            $this->dispatchBrowserEvent('selectedOrganisation', [
                'id' => $value,
                'address_line1' => $address->line1 ?? null,
                'address_line2' => $address->line2 ?? null,
                'address_line3' => $address->line3 ?? null,
                'address_city' => $address->city ?? null,
                'address_state' => $address->state ?? null,
                'address_code' => $address->code ?? null,
                'address_country' => $address->country ?? null,
            ]);
            $this->organisation_name = $organisation->name;
        } else {
            $this->dispatchBrowserEvent('selectedOrganisation');
        }
    }

    public function updatedOrganisationName($value)
    {
        $this->generateTitle();
    }

    public function updatedPersonId($value)
    {
        if ($person = Person::find($value)) {
            $email = $person->getPrimaryEmail();
            $phone = $person->getPrimaryPhone();
            $this->dispatchBrowserEvent('selectedPerson', [
                'id' => $value,
                'email' => $email->address ?? null,
                'email_type' => $email->type ?? null,
                'phone' => $phone->number ?? null,
                'phone_type' => $phone->type ?? null,
            ]);
        } else {
            $this->dispatchBrowserEvent('selectedPerson');
        }
    }

    public function updatedPersonName($value)
    {
        $this->generateTitle();
    }

    public function generateTitle()
    {
        if ($this->generateTitle) {
            if ($this->client_name) {
                $this->title = $this->client_name . ' ' . ucfirst(trans('laravel-crm::lang.deal'));
            } elseif ($this->organisation_name) {
                $this->title = $this->organisation_name . ' ' . ucfirst(trans('laravel-crm::lang.deal'));
            } elseif ($this->person_name) {
                $this->title = $this->person_name . ' ' . ucfirst(trans('laravel-crm::lang.deal'));
            }
        }
    }

    public function updatedTitle($value)
    {
        $this->generateTitle = false;
    }

    public function getClientOrganisations()
    {
        foreach (Client::find($this->client_id)->contacts()
                     ->where('entityable_type', 'LIKE', '%Organisation%')
                     ->get() as $contact) {
            $this->organisations[$contact->entityable_id] = $contact->entityable->name;
            $this->clientHasOrganisations = true;
        }
    }

    public function getClientPeople()
    {
        foreach (Client::find($this->client_id)->contacts()
                     ->where('entityable_type', 'LIKE', '%Person%')
                     ->get() as $contact) {
            $this->people[$contact->entityable_id] = $contact->entityable->name;
            $this->clientHasPeople = true;
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.deal-form');
    }
}
