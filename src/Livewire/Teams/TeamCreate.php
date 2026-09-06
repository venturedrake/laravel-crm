<?php

namespace VentureDrake\LaravelCrm\Livewire\Teams;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Teams\Traits\HasTeamCommon;
use VentureDrake\LaravelCrm\Models\Team;

class TeamCreate extends Component
{
    use AuthorizesRequests, HasTeamCommon;

    public $layout = 'full';

    public function mount()
    {
        $this->mountCommon();
    }

    public function save()
    {
        $this->authorize('create', Team::class);

        $this->validate();

        $user = auth()->user();

        $team = Team::create([
            'name' => $this->name,
            'user_id' => $user->id,
        ]);

        $userIds = collect($this->teamUsers)->push($user->id)->unique()->values()->all();
        $team->users()->sync($userIds);

        if (method_exists($user, 'switchTeam')) {
            $user->switchTeam($team);
        } elseif (Schema::hasColumn($user->getTable(), 'current_team_id')) {
            $user->forceFill(['current_team_id' => $team->id])->save();
        }

        // The relation still holds the team we just switched away from, and
        // anything reading it later this request — the settings cache key
        // included — would resolve to the wrong tenant.
        $user->unsetRelation('currentTeam');
        app('laravel-crm.settings')->forgetCache();

        $this->success(
            ucfirst(trans('laravel-crm::lang.team_created')),
            redirectTo: route('laravel-crm.dashboard')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.teams.team-create');
    }
}
