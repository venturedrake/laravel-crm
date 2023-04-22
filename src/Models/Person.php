<?php

namespace VentureDrake\LaravelCrm\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kyslik\ColumnSortable\Sortable;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasCrmActivities;
use VentureDrake\LaravelCrm\Traits\HasCrmFields;
use VentureDrake\LaravelCrm\Traits\HasGlobalSettings;
use VentureDrake\LaravelCrm\Traits\SearchFilters;
use VentureDrake\LaravelEncryptable\Traits\LaravelEncryptableTrait;

class Person extends Model
{
    use SoftDeletes;
    use LaravelEncryptableTrait;
    use BelongsToTeams;
    use HasCrmFields;
    use SearchFilters;
    use Sortable;
    use HasCrmActivities;
    use HasGlobalSettings;

    protected $guarded = ['id'];

    protected $encryptable = [
        'title',
        'first_name',
        'middle_name',
        'last_name',
        'maiden_name',
    ];

    protected $searchable = [
        'first_name',
        'middle_name',
        'last_name',
        'maiden_name',
    ];

    protected $filterable = [
        'user_owner_id',
        'labels.id',
    ];

    public $sortable = [
        'id',
        'first_name',
        'last_name',
        'created_at',
        'updated_at',
    ];

    public function getSearchable()
    {
        return $this->searchable;
    }

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'people';
    }

    public function getNameAttribute()
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function setBirthdayAttribute($value)
    {
        if ($value) {
            $this->attributes['birthday'] = Carbon::createFromFormat($this->dateFormat(), $this->decryptField($value));
        }
    }

    public function getBirthdayAttribute($value)
    {
        if ($value) {
            return Carbon::parse($this->decryptField($value))->format($this->dateFormat());
        }
    }

    public function getFirstNameDecryptedAttribute()
    {
        return $this->decryptField($this->first_name);
    }

    /**
     * Get all of the persons emails.
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
     * Get all of the persons phone numbers.
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

    public function getPrimaryAddress()
    {
        return $this->addresses()->where('primary', 1)->first();
    }

    public function organisation()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\Organisation::class);
    }

    public function deals()
    {
        return $this->hasMany(\VentureDrake\LaravelCrm\Models\Deal::class);
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

    /**
     * Get all of the labels for the person.
     */
    public function labels()
    {
        return $this->morphToMany(\VentureDrake\LaravelCrm\Models\Label::class, config('laravel-crm.db_table_prefix').'labelable');
    }

    public function contacts()
    {
        return $this->morphMany(\VentureDrake\LaravelCrm\Models\Contact::class, 'contactable');
    }

    /**
     * Get the xero person associated with the person.
     */
    public function xeroPerson()
    {
        return $this->hasOne(\VentureDrake\LaravelCrm\Models\XeroPerson::class);
    }
}
