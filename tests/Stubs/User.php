<?php

namespace VentureDrake\LaravelCrm\Tests\Stubs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $table = 'users';

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function hasPermissionTo($permission, $guardName = null): bool
    {
        $raw = $this->attributes['crm_permissions'] ?? null;

        if ($raw === null) {
            return true;
        }

        $list = is_array($raw) ? $raw : (json_decode($raw, true) ?: []);

        $name = is_object($permission) ? ($permission->name ?? '') : (string) $permission;

        return in_array($name, $list, true);
    }

    public function getCurrentTeamAttribute()
    {
        if (array_key_exists('currentTeam', $this->relations)) {
            return $this->relations['currentTeam'];
        }

        $id = $this->attributes['current_team_id'] ?? null;

        return $id ? (object) ['id' => (int) $id, 'name' => 'Team '.(int) $id, 'user_id' => 0] : null;
    }

    public function allTeams()
    {
        $raw = $this->attributes['team_ids'] ?? null;

        if (! $raw) {
            return collect();
        }

        $ids = is_array($raw) ? $raw : (json_decode($raw, true) ?: []);

        return collect($ids)->map(fn ($id) => (object) ['id' => (int) $id, 'name' => 'Team '.(int) $id, 'user_id' => 0]);
    }
}
