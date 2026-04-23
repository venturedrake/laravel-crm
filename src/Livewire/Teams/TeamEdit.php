<?php

namespace VentureDrake\LaravelCrm\Livewire\Teams;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Teams\Traits\HasTeamCommon;
use VentureDrake\LaravelCrm\Models\Team;

class TeamEdit extends Component
{
    use HasTeamCommon;

    public Team $team;

    public $layout = 'full';

    public function mount()
    {
        $this->mountCommon();

        $this->name = $this->team->name;
        $this->user_id = $this->team->user_id;
        $this->team_users = $this->team->users->pluck('id')->toArray();
    }

    public function save()
    {
        $this->validate();

        $this->team->update([
            'name' => $this->name,
        ]);

        if ($this->teamUsers) {
            $this->team->users()->sync($this->teamUsers);
        } else {
            $this->team->users()->sync([]);
        }

        $this->success(
            ucfirst(trans('laravel-crm::lang.team_updated')),
            redirectTo: route('laravel-crm.teams.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.teams.team-edit');
    }
}
