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

    public $showOrganizations = false;

    public $organizations;

    public $person_id;

    public $person;
    public $showPeople = false;

    public $people;

    public $title;
    
    public $description;
    
    public $amount;

    public $currency;

    public $pipeline;
    
    public $pipeline_stage_id;

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
        $this->organization_id = $lead->organization ? $lead->organization->id : null;
        $this->organization = $lead->organization ? $lead->organization->name : null;
        $this->person_id = $lead->person ? $lead->person->id : null;
        $this->person = $lead->person ? $lead->person->name : null;
        $this->title = $lead->title;
        $this->description = $lead->description;
        $this->amount = $lead->amount;
        $this->currency = $lead->currency;
        $this->pipeline = Pipeline::where('model', get_class(new Lead))->first();
        $this->pipeline_stage_id = $lead->pipelineStage->id ?? null;
        $this->labels = $lead->labels->pluck('id')->toArray();
        $this->user_owner_id = $lead->userOwner->id ?? null;

        if($address = $lead->getPrimaryAddress()){
            $this->address_line_1 = $address->line_1;
            $this->address_line_2 = $address->line_2;
            $this->address_line_3 = $address->line_3;
            $this->address_suburb = $address->suburb;
            $this->address_state = $address->state;
            $this->address_postcode = $address->postcode;
            $this->address_country = $address->country;
        }
        
        if($email = $lead->getPrimaryEmail()){
            $this->email = $email->address;
            $this->email_type = $email->type;
        }
        
        if($phone = $lead->getPrimaryPhone()){
            $this->phone = $phone->number;
            $this->phone_type = $phone->type;
        };
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
