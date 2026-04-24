<?php

namespace VentureDrake\LaravelCrm\Livewire\Users\Traits;

use Mary\Traits\Toast;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\AddressType;
use VentureDrake\LaravelCrm\Models\Email;
use VentureDrake\LaravelCrm\Models\Phone;
use VentureDrake\LaravelCrm\Models\Role;
use VentureDrake\LaravelCrm\Models\Team;

trait HasUserCommon
{
    use Toast;

    public $name;

    public $email;

    public $password;

    public $password_confirmation;

    public $crm_access;

    public $role;

    public $userTeams = [];

    public $roles = [
        '' => '',
    ];

    public $teams = [];

    public array $phoneTypes = [];

    public array $phones = [];

    public array $addressTypes = [
        [
            'id' => null,
            'name' => null,
        ],
    ];

    public array $countries = [];

    public array $addresses = [];

    public function mountCommon()
    {
        foreach (Role::crm()->when(config('laravel-crm.teams'), function ($query) {
            return $query->where('team_id', auth()->user()->currentTeam->id);
        })->get() as $role) {
            $this->roles[] = [
                'id' => $role->id,
                'name' => $role->name,
            ];
        }

        $this->teams = Team::orderBy('name', 'ASC')->get();

        $this->phoneTypes = \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\phoneTypes();

        foreach (AddressType::all() as $addressType) {
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

    protected function updateUserPhones($user, $phones)
    {
        $phoneIds = [];
        if ($phones) {
            foreach ($phones as $phoneRequest) {
                if ($phoneRequest['id'] && $phone = Phone::find($phoneRequest['id'])) {
                    $phone->update([
                        'number' => $phoneRequest['number'],
                        'type' => $phoneRequest['type'],
                        'primary' => ((isset($phoneRequest['primary']) && $phoneRequest['primary'] == 'on') ? 1 : 0),
                    ]);
                    $phoneIds[] = $phone->id;
                } elseif ($phoneRequest['number']) {
                    $phone = $user->phones()->create([
                        'external_id' => Uuid::uuid4()->toString(),
                        'number' => $phoneRequest['number'],
                        'type' => $phoneRequest['type'],
                        'primary' => ((isset($phoneRequest['primary']) && $phoneRequest['primary'] == 'on') ? 1 : 0),
                    ]);
                    $phoneIds[] = $phone->id;
                }
            }
        }

        foreach ($user->phones as $phone) {
            if (! in_array($phone->id, $phoneIds)) {
                $phone->delete();
            }
        }
    }

    protected function updateUserEmails($user, $emails)
    {
        $emailIds = [];

        if ($emails) {
            foreach ($emails as $emailRequest) {
                if ($emailRequest['id'] && $email = Email::find($emailRequest['id'])) {
                    $email->update([
                        'address' => $emailRequest['address'],
                        'type' => $emailRequest['type'],
                        'primary' => ((isset($emailRequest['primary']) && $emailRequest['primary'] == 'on') ? 1 : 0),
                    ]);

                    $emailIds[] = $email->id;
                } elseif ($emailRequest['address']) {
                    $email = $user->emails()->create([
                        'external_id' => Uuid::uuid4()->toString(),
                        'address' => $emailRequest['address'],
                        'type' => $emailRequest['type'],
                        'primary' => ((isset($emailRequest['primary']) && $emailRequest['primary'] == 'on') ? 1 : 0),
                    ]);

                    $emailIds[] = $email->id;
                }
            }
        }

        foreach ($user->emails as $email) {
            if (! in_array($email->id, $emailIds)) {
                $email->delete();
            }
        }
    }

    protected function updateUserAddresses($user, $addresses)
    {
        $addressIds = [];

        if ($addresses) {
            foreach ($addresses as $addressRequest) {
                if ($addressRequest['id'] && $address = Address::find($addressRequest['id'])) {
                    $address->update([
                        'address_type_id' => $addressRequest['type'] ?? null,
                        'address' => $addressRequest['address'] ?? null,
                        'name' => $addressRequest['name'] ?? null,
                        'contact' => $addressRequest['contact'] ?? null,
                        'phone' => $addressRequest['phone'] ?? null,
                        'line1' => $addressRequest['line1'],
                        'line2' => $addressRequest['line2'],
                        'line3' => $addressRequest['line3'],
                        'city' => $addressRequest['city'],
                        'state' => $addressRequest['state'],
                        'code' => $addressRequest['code'],
                        'country' => $addressRequest['country'],
                        'primary' => ((isset($addressRequest['primary']) && $addressRequest['primary'] == 'on') ? 1 : 0),
                    ]);

                    $addressIds[] = $address->id;
                } else {
                    $address = $user->addresses()->create([
                        'external_id' => Uuid::uuid4()->toString(),
                        'address_type_id' => $addressRequest['type'] ?? null,
                        'address' => $addressRequest['address'] ?? null,
                        'name' => $addressRequest['name'] ?? null,
                        'contact' => $addressRequest['contact'] ?? null,
                        'phone' => $addressRequest['phone'] ?? null,
                        'line1' => $addressRequest['line1'],
                        'line2' => $addressRequest['line2'],
                        'line3' => $addressRequest['line3'],
                        'city' => $addressRequest['city'],
                        'state' => $addressRequest['state'],
                        'code' => $addressRequest['code'],
                        'country' => $addressRequest['country'],
                        'primary' => ((isset($addressRequest['primary']) && $addressRequest['primary'] == 'on') ? 1 : 0),
                    ]);

                    $addressIds[] = $address->id;
                }
            }
        }

        foreach ($user->addresses as $address) {
            if (! in_array($address->id, $addressIds)) {
                $address->delete();
            }
        }
    }
}
