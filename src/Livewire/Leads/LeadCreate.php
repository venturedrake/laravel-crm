<?php

namespace VentureDrake\LaravelCrm\Livewire\Leads;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;

class LeadCreate extends Component
{
    public $organization_id;

    public $organization;

    public $person_id;

    public $person;

    public $title;

    public $showOrganizations = false;

    public $organizations;

    public $showPeople = false;

    public $people;

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
        }

        $this->showPeople = false;
    }

    public function hidePeople()
    {
        $this->showPeople = false;
    }

    public function render()
    {
        return view('laravel-crm::livewire.leads.lead-create');
    }
}
