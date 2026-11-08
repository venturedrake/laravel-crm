<?php

namespace VentureDrake\LaravelCrm\Livewire\Users;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use VentureDrake\LaravelCrm\Http\Rules\AssignableRole;
use VentureDrake\LaravelCrm\Livewire\Users\Traits\HasUserCommon;
use VentureDrake\LaravelCrm\Models\Role;

class UserEdit extends Component
{
    use AuthorizesRequests, HasUserCommon;

    public $user;

    public $layout = 'full';

    protected function rules()
    {
        return [
            'name' => 'required|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$this->user->id,
            'role' => ['nullable', new AssignableRole],
        ];
    }

    public function mount()
    {
        $this->mountCommon();

        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->crm_access = (bool) $this->user->crm_access;
        // Prefill from the target's current CRM role, but only when this caller could
        // actually hand that role out again. roles() is the unfiltered Spatie relation,
        // so a host-app role (crm_role = 0) or an Owner being edited by a non-Owner
        // would otherwise be bounced by the AssignableRole rule on a save that never
        // touched the field -- making the whole record unsaveable, name and phone
        // included. Leaving it null is a safe no-op: save() only removes and reassigns
        // when a role was actually chosen.
        $currentRole = $this->user->roles()->where('crm_role', 1)->first();

        $this->role = $currentRole && Role::assignableBy()->whereKey($currentRole->getKey())->exists()
            ? $currentRole->getKey()
            : null;

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
        $this->authorize('update', $this->user);

        $this->validate();

        // Vet the role before any of the record is persisted -- otherwise a 403
        // still commits the name/email/crm_access changes. See UserCreate::save().
        $role = $this->role ? Role::assignable()->find($this->role) : null;

        abort_if($role && $role->name === 'Owner' && ! auth()->user()->hasRole('Owner'), 403);

        $this->user->forceFill([
            'name' => $this->name,
            'email' => $this->email,
            'crm_access' => $this->crm_access,
        ])->save();

        if ($role) {
            if ($removeRole = $this->user->roles()->where('crm_role', 1)->first()) { // THIS COULD BE A BUG
                $this->user->removeRole($removeRole);
            }

            $this->user->assignRole($role);
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
            redirectTo: route('laravel-crm.users.show', $this->user)
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.users.user-edit');
    }
}
