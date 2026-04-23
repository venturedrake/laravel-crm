<?php

namespace VentureDrake\LaravelCrm\Livewire\Users;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Users\Traits\HasUserCommon;

class UserEdit extends Component
{
    use HasUserCommon;

    public $user;

    public $layout = 'full';

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
    }

    public function save()
    {
        $this->validate();

        // Create a request object to pass to services
        $request = \VentureDrake\LaravelCrm\Http\Helpers\PublicProperties\asRequest($this);

        //
    }

    public function render()
    {
        return view('laravel-crm::livewire.users.user-edit');
    }
}
