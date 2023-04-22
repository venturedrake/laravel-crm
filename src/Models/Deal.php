<?php

namespace VentureDrake\LaravelCrm\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasCrmActivities;
use VentureDrake\LaravelCrm\Traits\HasCrmFields;
use VentureDrake\LaravelCrm\Traits\HasGlobalSettings;
use VentureDrake\LaravelCrm\Traits\SearchFilters;

class Deal extends Model
{
    use SoftDeletes;
    use HasCrmFields;
    use BelongsToTeams;
    use SearchFilters;
    use HasCrmActivities;
    use HasGlobalSettings;

    protected $guarded = ['id'];

    protected $casts = [
        'expected_close' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected $searchable = [
        'title',
        'person.first_name',
        'person.middle_name',
        'person.last_name',
        'person.maiden_name',
        'organisation.name',
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
        return config('laravel-crm.db_table_prefix').'deals';
    }

    public function setAmountAttribute($value)
    {
        if (isset($value)) {
            $this->attributes['amount'] = $value * 100;
        } else {
            $this->attributes['amount'] = null;
        }
    }

    public function setExpectedCloseAttribute($value)
    {
        if ($value) {
            $this->attributes['expected_close'] = Carbon::createFromFormat($this->dateFormat(), $value);
        }
    }

    public function person()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\Person::class);
    }

    public function organisation()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\Organisation::class);
    }

    public function client()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\Client::class);
    }

    public function dealProducts()
    {
        return $this->hasMany(\VentureDrake\LaravelCrm\Models\DealProduct::class);
    }

    /**
     * Get all of the lead's custom field values.
     */
    public function customFieldValues()
    {
        return $this->morphMany(\VentureDrake\LaravelCrm\Models\FieldValue::class, 'custom_field_valueable');
    }

    public function createdByUser()
    {
        return $this->belongsTo(\App\User::class, 'user_created_id');
    }

    public function updatedByUser()
    {
        return $this->belongsTo(\App\User::class, 'user_updated_id');
    }

    public function deletedByUser()
    {
        return $this->belongsTo(\App\User::class, 'user_deleted_id');
    }

    public function restoredByUser()
    {
        return $this->belongsTo(\App\User::class, 'user_restored_id');
    }

    public function ownerUser()
    {
        return $this->belongsTo(\App\User::class, 'user_owner_id');
    }

    public function assignedToUser()
    {
        return $this->belongsTo(\App\User::class, 'user_assigned_id');
    }

    /**
     * Get all of the labels for the lead.
     */
    public function labels()
    {
        return $this->morphToMany(\VentureDrake\LaravelCrm\Models\Label::class, config('laravel-crm.db_table_prefix').'labelable');
    }
}
