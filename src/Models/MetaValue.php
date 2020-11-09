<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $table = 'crm_meta_values';
    
    protected $guarded = [];
}
