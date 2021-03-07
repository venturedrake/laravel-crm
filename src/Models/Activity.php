<?php

namespace App;

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use SoftDeletes;

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
