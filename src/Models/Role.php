<?php

namespace VentureDrake\LaravelCrm\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    public function scopeCrm($query)
    {
        return $query->where('crm_role', 1);
    }

    public function scopeCrmNotOwner($query)
    {
        return $query->where('crm_role', 1)->where('name', '<>', 'Owner');
    }
}
