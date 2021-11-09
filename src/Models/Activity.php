<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

class Activity extends Model
{
    use SoftDeletes;
    use BelongsToTeams;

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'activities';
    }

    /**
     * Get all of the owning activityable models.
     */
    public function activityable()
    {
        return $this->morphTo();
    }
}
