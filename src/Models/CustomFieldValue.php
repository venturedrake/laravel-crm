<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomFieldValue extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'custom_field_values';
    }

    /**
     * Get all of the owning custom_field_values models.
     */
    public function customFieldValueable()
    {
        return $this->morphTo();
    }
}
