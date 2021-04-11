<?php

namespace VentureDrake\LaravelCrm\Models;

use App\User as LaravelUser;
use Spatie\Permission\Traits\HasRoles;

class User extends LaravelUser
{
    use HasRoles;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password','crm_access','last_online_at',
    ];
    
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
