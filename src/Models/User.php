<?php

namespace VentureDrake\LaravelCrm\Models;

use App\User as LaravelUser;

class User extends LaravelUser
{
    /**
     * Get all of the teams the user belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function teams()
    {
        return $this->belongsToMany(\VentureDrake\LaravelCrm\Models\Team::class, 'crm_team_user', 'user_id', 'crm_team_id');
    }

    /**
     * Determine if the user owns the given team.
     *
     * @param  mixed  $team
     * @return bool
     */
    public function ownsTeam($team)
    {
        return $this->id == $team->{$this->getForeignKey()};
    }
    
    /**
     * Determine if the user belongs to the given team.
     *
     * @param  mixed  $team
     * @return bool
     */
    public function belongsToTeam($team)
    {
        return $this->teams()->where('crm_team_id', $team->id)->exists();
    }
}
