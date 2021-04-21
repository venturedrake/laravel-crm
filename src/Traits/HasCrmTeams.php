<?php

namespace VentureDrake\LaravelCrm\Traits;

use VentureDrake\LaravelCrm\Models\Team;

trait HasCrmTeams
{
    /**
     * Get all of the teams the user belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function crmTeams()
    {
        return $this->belongsToMany(Team::class, 'crm_team_user', 'user_id', 'crm_team_id');
    }

    /**
     * Determine if the user owns the given team.
     *
     * @param  mixed  $team
     * @return bool
     */
    public function ownsCrmTeam($team)
    {
        return $this->id == $team->{$this->getForeignKey()};
    }

    /**
     * Determine if the user belongs to the given team.
     *
     * @param  mixed  $team
     * @return bool
     */
    public function belongsToCrmTeam($team)
    {
        return $this->crmTeams()->where('crm_team_id', $team->id)->exists();
    }
}
