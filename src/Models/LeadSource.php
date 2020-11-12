<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadSource extends Model
{
    use SoftDeletes;
    
    protected $table = 'crm_lead_sources';

    protected $guarded = ['id'];

    public function leads()
    {
        return $this->hasMany(\VentureDrake\LaravelCrm\Models\Lead::class, 'lead_status_id');
    }
}
