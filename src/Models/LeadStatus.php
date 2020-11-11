<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Model;

class LeadStatus extends Model
{
    protected $table = 'crm_lead_statuses';

    protected $guarded = ['id'];

    public function leads()
    {
        return $this->hasMany(\VentureDrake\LaravelCrm\Models\Lead::class, 'lead_status_id');
    }
}
