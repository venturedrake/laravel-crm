<?php

namespace VentureDrake\LaravelCrm\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

class Permission extends SpatiePermission
{
    use BelongsToTeams;
    
    public function scopeCrm($query)
    {
        return $query->where('crm_permission', 1);
    }
}
