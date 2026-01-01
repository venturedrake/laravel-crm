<?php

namespace VentureDrake\LaravelCrm\Livewire\Orders\Traits;

use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Services\LeadService;
use VentureDrake\LaravelCrm\Services\OrganizationService;
use VentureDrake\LaravelCrm\Services\PersonService;

trait HasOrderCommon
{
    use Toast;

    protected LeadService $leadService;

    protected PersonService $personService;

    protected OrganizationService $organizationService;

    public $person_id;

    public $person_name;

    public $phone;

    public $phone_type = 'mobile';

    public $email;

    public $email_type;

    public $organization_id;

    public $organization_name;

    public $address_line_1;

    public $address_line_2;

    public $address_line_3;

    public $address_suburb;

    public $address_state;

    public $address_postcode;

    public $address_country = 'United States';

    public $title;

    public $description;

    public $amount;

    public $currency;

    public $pipeline;

    public $pipeline_stage_id;

    public array $labels;

    public $user_owner_id;

    protected function rules()
    {
        return [
            'person_name' => 'required_without_all:organization_name,organization_id|max:255',
            'person_id' => 'required_without_all:organization_name,organization_id,person_name|max:255',
            'organization_name' => 'required_without_all:person_name,person_id|max:255',
            'organization_id' => 'required_without_all:person_name,person_id,organization_name|max:255',
            'title' => 'required|max:255',
            'amount' => 'nullable|numeric',
        ];
    }

    protected function messages()
    {
        return [
            'person_name.required_without_all' => 'The contact person field is required if no organization.',
            'organization_name.required_without_all' => 'The organization field is required if no contact person.',
            'person_id.required_without_all' => 'The contact person field is required if no organization.',
            'organization_id.required_without_all' => 'The organization field is required of no contact person.',
        ];
    }

    public function boot(LeadService $leadService, PersonService $personService, OrganizationService $organizationService): void
    {
        $this->leadService = $leadService;
        $this->personService = $personService;
        $this->organizationService = $organizationService;
    }
}
