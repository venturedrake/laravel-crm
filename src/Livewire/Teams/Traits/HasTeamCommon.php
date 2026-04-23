<?php

namespace VentureDrake\LaravelCrm\Livewire\Teams\Traits;

use App\Models\User;
use Mary\Traits\Toast;

trait HasTeamCommon
{
    use Toast;

    public $name;

    public $user_id;

    public $team_users = [];

    public $users = [];

    public $teamUsers = [];

    protected function rules()
    {
        return [
            'name' => 'required|max:255',
        ];
    }

    public function mountCommon()
    {
        $this->users = User::orderBy('name', 'ASC')->get();
    }
}
