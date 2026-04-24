<?php

namespace VentureDrake\LaravelCrm\Livewire\Users;

use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Users\Traits\HasUserCommon;
use VentureDrake\LaravelCrm\Models\Role;

class UserCreate extends Component
{
    use HasUserCommon;

    public $layout = 'full';

    protected function rules()
    {
        return [
            'name' => 'required|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function mount()
    {
        $this->mountCommon();

        $this->addPhone();

        $this->addAddress();
    }

    public function save()
    {
        $this->validate();

        $user = User::forceCreate([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'crm_access' => $this->crm_access,
        ]);

        if ($this->role) {
            if ($role = Role::find($this->role)) {
                if ($removeRole = $user->roles()->where('crm_role', 1)->first()) { // THIS COULD BE A BUG
                    $user->removeRole($removeRole);
                }

                $user->assignRole($role);
            }
        }

        $this->updateUserPhones($user, $this->phones);
        $this->updateUserAddresses($user, $this->addresses);

        if (config('laravel-crm.teams')) {
            if ($team = auth()->user()->currentTeam) {
                DB::table('team_user')->insert([
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                    'role' => 'editor', // Default Jetstream role
                ]);

                $user->forceFill([
                    'current_team_id' => $team->id,
                ])->save();
            }
        }

        if ($this->userTeams) {
            $user->crmTeams()->sync($this->userTeams);
        } else {
            $user->crmTeams()->sync([]);
        }

        $this->success(
            ucfirst(trans('laravel-crm::lang.user_created')),
            redirectTo: route('laravel-crm.users.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.users.user-create');
    }
}
