<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

class Setting extends Model
{
    use BelongsToTeams;
    
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
