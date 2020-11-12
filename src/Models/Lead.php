<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\HasCustomFields;

class Lead extends Model
{
    use SoftDeletes;
    use HasCustomFields;
    
    protected $table = 'crm_leads';
    
    protected $guarded = ['id','external_id'];

    /**
     * Get all of the lead's emails.
     */
    public function emails()
    {
        return $this->morphMany(\VentureDrake\LaravelCrm\Models\Email::class, 'emailable');
    }
    
    public function getPrimaryEmail()
    {
        return $this->emails()->where('primary', 1)->first();
    }

    /**
     * Get all of the lead's phone numbers.
     */
    public function phones()
    {
        return $this->morphMany(\VentureDrake\LaravelCrm\Models\Phone::class, 'phoneable');
    }

    public function getPrimaryPhone()
    {
        return $this->phones()->where('primary', 1)->first();
    }

    /**
     * Get all of the leads addresses.
     */
    public function addresses()
    {
        return $this->morphMany(\VentureDrake\LaravelCrm\Models\Address::class, 'addressable');
    }

    public function leadStatus()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\LeadStatus::class, 'lead_status_id');
    }

    public function leadSource()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\LeadSource::class, 'lead_source_id');
    }

    /**
     * Get all of the lead's custom field values.
     */
    public function customFieldValues()
    {
        return $this->morphMany(\VentureDrake\LaravelCrm\Models\CustomFieldValue::class, 'custom_field_valueable');
    }
}
