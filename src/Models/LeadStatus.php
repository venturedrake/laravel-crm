<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Model;

class LeadStatus extends Model
{
    protected $guarded = ['id'];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').parent::getTable();
    }

    public function leads()
    {
        return $this->hasMany(\VentureDrake\LaravelCrm\Models\Lead::class, 'lead_status_id');
    }
}
