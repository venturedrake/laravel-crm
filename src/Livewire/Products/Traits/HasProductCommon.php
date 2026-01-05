<?php

namespace VentureDrake\LaravelCrm\Livewire\Products\Traits;

use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Services\LeadService;
use VentureDrake\LaravelCrm\Services\OrganizationService;
use VentureDrake\LaravelCrm\Services\PersonService;

trait HasProductCommon
{
    use Toast;

    protected LeadService $leadService;

    protected PersonService $personService;

    protected OrganizationService $organizationService;

    public $title;

    public $description;

    public $amount;

    public $currency;

    public array $labels;

    public array $taxRates = [
        ['id' => null, 'name' => null],
    ];

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

    public function mountCommon()
    {
        foreach (\VentureDrake\LaravelCrm\Models\TaxRate::get() as $taxRate) {
            $this->taxRates[] = [
                'id' => $taxRate->id,
                'name' => $taxRate->name,
                'rate' => $taxRate->rate,
            ];
        }

        $this->currency = \VentureDrake\LaravelCrm\Models\Setting::currency()->value ?? 'USD';
        $this->user_owner_id = auth()->user()->id;
    }
}
