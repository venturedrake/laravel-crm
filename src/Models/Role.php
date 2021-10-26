<?php

namespace VentureDrake\LaravelCrm\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use VentureDrake\LaravelCrm\Scopes\BelongsToTeamsScope;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

class Role extends SpatieRole
{
    use BelongsToTeams;
    
    public function scopeCrm($query)
    {
        return $query->where('crm_role', 1);
    }

    public function scopeCrmNotOwner($query)
    {
        return $query->where('crm_role', 1)->where('name', '<>', 'Owner');
    }

    public function scopeAllTeams($query)
    {
        return $query->withoutGlobalScope(BelongsToTeamsScope::class);
    }
}
