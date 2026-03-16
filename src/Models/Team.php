<?php

namespace VentureDrake\LaravelCrm\Models;

use App\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

class Team extends Model
{
    use BelongsToTeams;
    use SoftDeletes;

    /**
     * The table associated with the team model.
     *
     * @var string
     */
    protected $table = 'crm_teams';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'user_id', 'personal_team',
    ];

    public function userCreated()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all of the users the team belongs to.
     *
     * @return BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'crm_team_user', 'crm_team_id', 'user_id');
    }
}
