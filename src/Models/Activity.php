<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

class Activity extends Model
{
    use SoftDeletes;
    use BelongsToTeams;

    protected $guarded = ['id'];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'activities';
    }

    public function causeable()
    {
        return $this->morphTo();
    }

    public function timelineable()
    {
        return $this->morphTo();
    }

    public function recordable()
    {
        return $this->morphTo();
    }
}
