<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomFieldValue extends Model
{
    use SoftDeletes;
    
    protected $table = 'crm_custom_field_values';

    protected $guarded = ['id'];

    /**
     * Get all of the owning custom_field_values models.
     */
    public function custom_field_valueable()
    {
        return $this->morphTo();
    }
}
