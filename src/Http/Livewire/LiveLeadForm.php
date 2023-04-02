<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;

class LiveLeadForm extends Component
{
    public $person_id;
    public $person_name;
    public $organisation_id;
    public $organisation_name;
    public $title;
    public $generateTitle;

    public function mount($lead, $generateTitle = true)
    {
        $this->person_id = old('person_id') ?? $lead->person->id ?? null;
        $this->person_name = old('person_name') ?? $lead->person->name ?? null;
        $this->organisation_id = old('organisation_id') ?? $lead->organisation->id ?? null;
        $this->organisation_name = old('organisation_name') ?? $lead->organisation->name ?? null;
        $this->title = old('title') ?? $lead->title ?? null;
        $this->generateTitle = $generateTitle;
    }

    public function updatedPersonName($value)
    {
        if ($this->generateTitle && ! $this->organisation_name) {
            $this->title = $value . ' ' . ucfirst(trans('laravel-crm::lang.lead'));
        }
    }

    public function updatedOrganisationName($value)
    {
        if ($this->generateTitle) {
            $this->title = $value . ' ' . ucfirst(trans('laravel-crm::lang.lead'));
        }
    }

    public function updatedTitle($value)
    {
        $this->generateTitle = false;
    }
    
    public function render()
    {
        return view('laravel-crm::livewire.live-lead-form');
    }
}
