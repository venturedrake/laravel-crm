<?php

namespace VentureDrake\LaravelCrm\Livewire\Teams;

use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Team;

class TeamShow extends Component
{
    use Toast;

    public Team $team;

    public function delete($id)
    {
        if ($team = Team::find($id)) {
            $team->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.team_deleted')), redirectTo: route('laravel-crm.teams.index'));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.teams.team-show');
    }
}
