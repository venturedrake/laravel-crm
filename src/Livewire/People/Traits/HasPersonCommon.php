<?php

namespace VentureDrake\LaravelCrm\Livewire\People\Traits;

use Carbon\Carbon;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Services\PersonService;

trait HasPersonCommon
{
    use Toast;

    protected PersonService $personService;

    public $title;

    public $first_name;

    public $last_name;

    public $middle_name;

    public array $genders = [
        [
            'id' => null,
            'name' => null,
        ],
        [
            'id' => 'male',
            'name' => 'Male',
        ],
        [
            'id' => 'female',
            'name' => 'Female',
        ],
    ];

    public $gender;

    public ?Carbon $birthday = null;

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
            'first_name' => 'required|max:255',
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

    public function boot(PersonService $personService): void
    {
        $this->personService = $personService;
    }

    public function mountCommon()
    {
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
