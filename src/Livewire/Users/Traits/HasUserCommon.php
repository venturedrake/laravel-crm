<?php

namespace VentureDrake\LaravelCrm\Livewire\Users\Traits;

use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Role;
use VentureDrake\LaravelCrm\Models\Team;

trait HasUserCommon
{
    use Toast;

    public $name;

    public $email;

    public $crm_access;

    public $role;

    public $userTeams = [];

    public $roles = [
        '' => '',
    ];

    public $teams = [];

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
    }
}
