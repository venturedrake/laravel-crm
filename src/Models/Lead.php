<?php

namespace VentureDrake\LaravelCrm\Models;

use App\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Support\Money;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasCrmActivities;
use VentureDrake\LaravelCrm\Traits\HasCrmFields;
use VentureDrake\LaravelCrm\Traits\HasPrimaryContactDetails;
use VentureDrake\LaravelCrm\Traits\SearchFilters;

class Lead extends Model
{
    use BelongsToTeams;
    use HasCrmActivities;
    use HasCrmFields;
    use HasPrimaryContactDetails;
    use SearchFilters;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'converted_at' => 'datetime',
    ];

    protected $searchable = [
        'lead_id',
        'title',
        'person.first_name',
        'person.middle_name',
        'person.last_name',
        'person.maiden_name',
        'organization.name',
    ];

    protected $filterable = [
        'user_owner_id',
        'labels.id',
    ];

    public function getSearchable()
    {
        return $this->searchable;
    }

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'leads';
    }

    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = Money::toInteger($value);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * Get all of the lead's emails.
     */
    public function emails()
    {
        return $this->morphMany(Email::class, 'emailable');
    }

    /**
     * A lead's contact details are the person's where there is one, so this
     * keeps its override of the HasPrimaryContactDetails getter. The fallback
     * still goes through the trait, so a `with('primaryEmail')` on a lead
     * without a person is honoured.
     */
    public function getPrimaryEmail()
    {
        if ($this->person) {
            return $this->person->getPrimaryEmail();
        }

        return $this->primaryContactDetail('primaryEmail', 'emails');
    }

    /**
     * Get all of the lead's phone numbers.
     */
    public function phones()
    {
        return $this->morphMany(Phone::class, 'phoneable');
    }

    public function getPrimaryPhone()
    {
        if ($this->person) {
            return $this->person->getPrimaryPhone();
        }

        return $this->primaryContactDetail('primaryPhone', 'phones');
    }

    /**
     * Get all of the leads addresses.
     */
    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function getPrimaryAddress()
    {
        if ($this->organization) {
            return $this->organization->getPrimaryAddress();
        }

        return $this->primaryContactDetail('primaryAddress', 'addresses');
    }

    public function leadStatus()
    {
        return $this->belongsTo(LeadStatus::class, 'lead_status_id');
    }

    public function leadSource()
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
    }

    /**
     * Get all of the lead's custom field values.
     */
    public function customFieldValues()
    {
        return $this->morphMany(FieldValue::class, 'custom_field_valueable');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'user_created_id');
    }

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'user_updated_id');
    }

    public function deletedByUser()
    {
        return $this->belongsTo(User::class, 'user_deleted_id');
    }

    public function restoredByUser()
    {
        return $this->belongsTo(User::class, 'user_restored_id');
    }

    public function ownerUser()
    {
        return $this->belongsTo(User::class, 'user_owner_id');
    }

    public function assignedToUser()
    {
        return $this->belongsTo(User::class, 'user_assigned_id');
    }

    /**
     * Get all of the labels for the lead.
     */
    public function labels()
    {
        return $this->morphToMany(Label::class, config('laravel-crm.db_table_prefix').'labelable');
    }

    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function pipelineStage()
    {
        return $this->belongsTo(PipelineStage::class);
    }
}
