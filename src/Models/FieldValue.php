<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

class FieldValue extends Model
{
    use BelongsToTeams;
    use SoftDeletes;

    protected $guarded = ['id'];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'field_values';
    }

    public function field()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\Field::class);
    }

    /**
     * Get all of the owning field value models.
     */
    public function fieldValueable()
    {
        return $this->morphTo();
    }
}
