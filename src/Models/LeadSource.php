<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

class LeadSource extends Model
{
    use SoftDeletes;
    use BelongsToTeams;

    protected $guarded = ['id'];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'lead_sources';
    }

    public function leads()
    {
        return $this->hasMany(\VentureDrake\LaravelCrm\Models\Lead::class, 'lead_source_id');
    }
}
