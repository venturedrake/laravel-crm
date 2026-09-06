<?php

namespace VentureDrake\LaravelCrm\Tests\Stubs;

use Illuminate\Foundation\Auth\User as Authenticatable;
use VentureDrake\LaravelCrm\Traits\HasCrmTeams;

/**
 * A host-app user on a install without Jetstream.
 *
 * The point of this stub is what it does *not* have: no switchTeam() and no
 * allTeams(), because both arrive together on Jetstream's HasTeams trait. That
 * combination is the one CurrentTeamController's fallback branch actually
 * serves, and the main User stub cannot stand in for it — it hand-defines
 * allTeams(), so it exercises the Jetstream path no matter which branch the
 * controller takes.
 */
class NonJetstreamUser extends Authenticatable
{
    use HasCrmTeams;

    protected $table = 'users';

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getCurrentTeamAttribute()
    {
        if (array_key_exists('currentTeam', $this->relations)) {
            return $this->relations['currentTeam'];
        }

        $id = $this->attributes['current_team_id'] ?? null;

        return $id ? (object) ['id' => (int) $id, 'name' => 'Team '.(int) $id, 'user_id' => 0] : null;
    }
}
