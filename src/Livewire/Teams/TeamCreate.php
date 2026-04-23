<?php

namespace VentureDrake\LaravelCrm\Livewire\Teams;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Teams\Traits\HasTeamCommon;
use VentureDrake\LaravelCrm\Models\Team;

class TeamCreate extends Component
{
    use HasTeamCommon;

    public $layout = 'full';

    public function mount()
    {
        $this->mountCommon();
    }

    public function save()
    {
        $this->validate();

        $team = Team::create([
            'name' => $this->name,
            'user_id' => auth()->user()->id,
        ]);

        if ($this->teamUsers) {
            $team->users()->sync($this->teamUsers);
        } else {
            $team->users()->sync([]);
        }

        $this->success(
            ucfirst(trans('laravel-crm::lang.team_created')),
            redirectTo: route('laravel-crm.teams.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.teams.team-create');
    }
}
