<?php

namespace VentureDrake\LaravelCrm\Livewire\Leads;

use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Pipeline;

class LeadEdit extends Component
{
    use Toast;

    public $organization_id;

    public $organization;

    public $person_id;

    public $person;

    public $title;

    public $showOrganizations = false;

    public $organizations;

    public $showPeople = false;

    public $people;

    public $currency;

    public $pipeline;

    public array $labels;

    public $user_owner_id;

    public $address_line_1;

    public $address_line_2;

    public $address_line_3;

    public $address_suburb;

    public $address_state;

    public $address_postcode;

    public $address_country = 'United States';

    public $phone;

    public $phone_type = 'mobile';

    public $email;

    public $email_type;

    public function mount(Lead $lead)
    {
        $this->lead = $lead;
        $this->currency = \VentureDrake\LaravelCrm\Models\Setting::currency()->value ?? 'USD';
        $this->pipeline = Pipeline::where('model', get_class(new Lead))->first();
        $this->user_owner_id = auth()->user()->id;
    }

    public function updatedOrganization($value)
    {
        $this->generateLeadString($value);
    }

    public function updatedPerson($value)
    {
        if (! $this->organization) {
            $this->generateLeadString($value);
        }
    }

    protected function generateLeadString($value)
    {
        $this->title = trim($value).' '.ucfirst(trans('laravel-crm::lang.lead'));
    }

    public function searchOrganizations()
    {

        if (! empty($this->organization)) {

            $this->organizations = Organization::orderby('name', 'asc')
                ->select('*')
                ->where('name', 'like', '%'.$this->organization.'%')
                ->limit(10)
                ->get();

            if ($this->organizations->count() > 0) {
                $this->showOrganizations = true;
            }
        } else {
            $this->showOrganizations = false;
        }
    }

    public function linkOrganization($id)
    {

        if ($organization = Organization::find($id)) {
            $this->organization_id = $id;
            $this->organization = $organization->name;
            $this->generateLeadString($organization->name);
        }

        $this->showOrganizations = false;
    }

    public function hideOrganizations()
    {
        $this->showOrganizations = false;
    }

    public function searchPeople()
    {

        if (! empty($this->person)) {

            $this->people = Person::orderby('first_name', 'asc')
                ->select('*')
                ->where('first_name', 'like', '%'.$this->person.'%')
                ->limit(10)
                ->get();

            if ($this->people->count() > 0) {
                $this->showPeople = true;
            }
        } else {
            $this->showPeople = false;
        }
    }

    public function linkPerson($id)
    {

        if ($person = Person::find($id)) {
            $this->person_id = $id;
            $this->person = $person->name;

            if (! $this->organization) {
                $this->generateLeadString($person->name);
            }
        }

        $this->showPeople = false;
    }

    public function hidePeople()
    {
        $this->showPeople = false;
    }

    public function save()
    {
        $this->success(ucfirst(trans('laravel-crm::lang.lead_updated_successfully')));
    }

    public function render()
    {
        return view('laravel-crm::livewire.leads.lead-edit');
    }
}
