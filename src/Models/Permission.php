<?php

namespace VentureDrake\LaravelCrm\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    public function scopeCrm($query)
    {
        return $query->where('crm_permission', 1);
    }
}
