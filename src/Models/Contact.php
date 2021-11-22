<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

class Contact extends Model
{
    use SoftDeletes;
    use BelongsToTeams;

    protected $guarded = ['id'];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'contacts';
    }

    public function contactTypes()
    {
        return $this->belongsToMany(ContactType::class);
    }

    /**
     * Get all of the owning contactable models.
     */
    public function contactable()
    {
        return $this->morphTo();
    }

    /**
     * Get all of the owning entityable models.
     */
    public function entityable()
    {
        return $this->morphTo();
    }
}
