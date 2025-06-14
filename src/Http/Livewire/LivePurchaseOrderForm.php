<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Organisation;
use VentureDrake\LaravelCrm\Models\Person;

class LivePurchaseOrderForm extends Component
{
    public $people = [];
    public $person_id;
    public $person_name;
    public $organisations = [];
    public $organisation_id;
    public $organisation_name;

    public function mount($purchaseOrder, $organisation = null, $person = null)
    {
        $this->person_id = old('person_id') ?? $purchaseOrder->person->id ?? $person->id ?? null;
        $this->person_name = old('person_name') ?? $purchaseOrder->person->name ?? $person->name ?? null;
        $this->organisation_id = old('organisation_id') ?? $purchaseOrder->organisation->id ?? $organisation->id ?? null;
        $this->organisation_name = old('organisation_name') ?? $purchaseOrder->organisation->name ?? $organisation->name ?? null;
    }

    public function updatedOrganisationId($value)
    {
        if ($organisation = Organisation::find($value)) {
            $this->organisation_name = $organisation->name;

            $this->emit('purchaseOrderOrganisationSelected', [
                'id' => $this->organisation_id,
            ]);
        }
    }

    public function updatedPersonId($value)
    {
        if ($person = Person::find($value)) {
            $this->person_name = $person->name;

            $this->emit('purchaseOrderPersonSelected', [
                'id' => $this->person_id,
            ]);
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.purchase-order-form');
    }
}
