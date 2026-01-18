<?php

namespace VentureDrake\LaravelCrm\Livewire\Organizations\Traits;

use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Services\OrganizationService;

trait HasOrganizationCommon
{
    use Toast;

    protected OrganizationService $organizationService;

    public $name;

    public array $organizationTypes = [
        [
            'id' => null,
            'name' => null,
        ],
    ];

    public $organization_type_id;

    public $vat_number;

    public array $industries = [
        [
            'id' => null,
            'name' => null,
        ],
    ];

    public $industry_id;

    public array $timezones = [
        [
            'id' => null,
            'name' => null,
        ],
    ];

    public $timezone_id;

    public $number_of_employees;

    public $annual_revenue;

    public $linkedin;

    public $description;

    public array $labels;

    public $user_owner_id;

    public array $phoneTypes = [];

    public array $phones = [];

    public array $emailTypes = [];

    public array $emails = [];

    public array $addressTypes = [
        [
            'id' => null,
            'name' => null,
        ],
    ];

    public array $countries = [];

    public array $addresses = [];

    protected function rules()
    {
        return [
            'name' => 'required|max:255',
            'phones.*.type' => 'required_with:phones.*.number',
            'emails.*.type' => 'required_with:emails.*.address',
        ];
    }

    protected function messages()
    {
        return [
            'phones.*.type.required_with' => 'The type field is required',
            'emails.*.type.required_with' => 'The type field is required',
        ];
    }

    public function boot(OrganizationService $organizationService): void
    {
        $this->organizationService = $organizationService;
    }

    public function mountCommon()
    {
        foreach (\VentureDrake\LaravelCrm\Models\OrganizationType::all() as $organizationType) {
            $this->organizationTypes[] = [
                'id' => $organizationType->id,
                'name' => $organizationType->name,
            ];
        }

        foreach (\VentureDrake\LaravelCrm\Models\Industry::all() as $industry) {
            $this->industries[] = [
                'id' => $industry->id,
                'name' => $industry->name,
            ];
        }

        foreach (\VentureDrake\LaravelCrm\Models\Timezone::all() as $timezone) {
            $this->timezones[] = [
                'id' => $timezone->id,
                'name' => $timezone->name,
            ];
        }

        $this->phoneTypes = \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\phoneTypes();
        $this->emailTypes = \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\emailTypes();

        foreach (\VentureDrake\LaravelCrm\Models\AddressType::all() as $addressType) {
            $this->addressTypes[] = [
                'id' => $addressType->id,
                'name' => $addressType->name,
            ];
        }

        $this->countries = \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\countries();
    }

    public function addPhone()
    {
        $this->phones[] = [
            'id' => null,
            'number' => null,
            'type' => null,
            'primary' => null,
        ];
    }

    public function deletePhone($index)
    {
        unset($this->phones[$index]);
    }

    public function addEmail()
    {
        $this->emails[] = [
            'id' => null,
            'address' => null,
            'type' => null,
            'primary' => null,
        ];
    }

    public function deleteEmail($index)
    {
        unset($this->emails[$index]);
    }

    public function addAddress()
    {
        $this->addresses[] = [
            'id' => null,
            'type' => null,
            'name' => null,
            'contact' => null,
            'phone' => null,
            'address' => null,
            'line1' => null,
            'line2' => null,
            'line3' => null,
            'city' => null,
            'state' => null,
            'code' => null,
            'country' => app('laravel-crm.settings')->get('country', 'United States'),
        ];
    }

    public function deleteAddress($index)
    {
        unset($this->addresses[$index]);
    }
}
