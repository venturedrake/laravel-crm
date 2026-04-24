<?php

namespace VentureDrake\LaravelCrm\Livewire\Users;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Users\Traits\HasUserCommon;
use VentureDrake\LaravelCrm\Models\Role;

class UserEdit extends Component
{
    use HasUserCommon;

    public $user;

    public $layout = 'full';

    protected function rules()
    {
        return [
            'name' => 'required|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$this->user->id,
        ];
    }

    public function mount()
    {
        $this->mountCommon();

        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->crm_access = (bool) $this->user->crm_access;
        $this->role = optional($this->user->roles()->first())->id;

        if (method_exists($this->user, 'crmTeams')) {
            $this->userTeams = $this->user->crmTeams()->pluck('crm_team_user.crm_team_id')->toArray();
        }

        if ($this->user->phones->count() == 0) {
            $this->addPhone();
        } else {
            foreach ($this->user->phones as $phone) {
                $this->phones[] = [
                    'id' => $phone->id,
                    'type' => $phone->type,
                    'number' => $phone->number,
                    'primary' => $phone->primary,
                ];
            }
        }

        if ($this->user->addresses->count() == 0) {
            $this->addAddress();
        } else {
            foreach ($this->user->addresses as $address) {
                $this->addresses[] = [
                    'id' => $address->id,
                    'type' => $address->address_type_id,
                    'primary' => $address->primary,
                    'name' => $address->name,
                    'contact' => $address->contact,
                    'phone' => $address->phone,
                    'address' => $address->address,
                    'line1' => $address->line1,
                    'line2' => $address->line2,
                    'line3' => $address->line3,
                    'city' => $address->city,
                    'state' => $address->state,
                    'code' => $address->code,
                    'country' => $address->country,
                ];
            }
        }
    }

    public function save()
    {
        $this->validate();

        $this->user->forceFill([
            'name' => $this->name,
            'email' => $this->email,
            'crm_access' => $this->crm_access,
        ])->save();

        if ($this->role) {
            if ($role = Role::find($this->role)) {
                if ($removeRole = $this->user->roles()->where('crm_role', 1)->first()) { // THIS COULD BE A BUG
                    $this->user->removeRole($removeRole);
                }

                $this->user->assignRole($role);
            }
        }

        $this->updateUserPhones($this->user, $this->phones);
        $this->updateUserAddresses($this->user, $this->addresses);

        if ($this->userTeams) {
            $this->user->crmTeams()->sync($this->userTeams);
        } else {
            $this->user->crmTeams()->sync([]);
        }

        $this->success(
            ucfirst(trans('laravel-crm::lang.user_updated')),
            redirectTo: route('laravel-crm.users.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.users.user-edit');
    }
}
