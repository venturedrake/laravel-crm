<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Organisation;
use VentureDrake\LaravelCrm\Models\Person;

class LiveInvoiceForm extends Component
{
    public $people = [];
    public $person_id;
    public $person_name;

    public $organisations = [];
    public $organisation_id;
    public $organisation_name;

    public function mount($invoice, $organisation = null, $person = null)
    {
        $this->person_id = old('person_id') ?? $invoice->person->id ?? $person->id ?? null;
        $this->person_name = old('person_name') ?? $invoice->person->name ?? $person->name ?? null;
        $this->organisation_id = old('organisation_id') ?? $invoice->organisation->id ?? $organisation->id ?? null;
        $this->organisation_name = old('organisation_name') ?? $invoice->organisation->name ?? $organisation->name ?? null;
    }

    public function updatedOrganisationId($value)
    {
        if ($organisation = Organisation::find($value)) {
            $this->organisation_name = $organisation->name;

            $this->emit('invoiceOrganisationSelected', [
                'id' => $this->organisation_id,
            ]);
        }
    }

    public function updatedPersonId($value)
    {
        if ($person = Person::find($value)) {
            $this->person_name = $person->name;

            $this->emit('invoicePersonSelected', [
                'id' => $this->person_id,
            ]);
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.invoice-form');
    }
}
