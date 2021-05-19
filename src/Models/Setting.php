<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $guarded = ['id'];
    
    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'settings';
    }

    public function scopeCurrency($query)
    {
        return $query->where('name', 'currency')->first();
    }
}
