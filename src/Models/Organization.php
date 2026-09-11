<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Support\Money;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasCrmActivities;
use VentureDrake\LaravelCrm\Traits\HasCrmFields;
use VentureDrake\LaravelCrm\Traits\HasCrmUserRelations;
use VentureDrake\LaravelCrm\Traits\HasEncryptableFields;
use VentureDrake\LaravelCrm\Traits\HasPrimaryContactDetails;
use VentureDrake\LaravelCrm\Traits\SearchFilters;

class Organization extends Model
{
    use BelongsToTeams;
    use HasCrmActivities;
    use HasCrmFields;
    use HasCrmUserRelations;
    use HasEncryptableFields;
    use HasPrimaryContactDetails;
    use SearchFilters;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $encryptable = [
        'name',
    ];

    protected $searchable = [
        'name',
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
        return config('laravel-crm.db_table_prefix').'organizations';
    }

    public function setAnnualRevenueAttribute($value)
    {
        $this->attributes['annual_revenue'] = Money::toInteger($value);
    }

    public function setTotalMoneyRaisedAttribute($value)
    {
        $this->attributes['total_money_raised'] = Money::toInteger($value);
    }

    public function people()
    {
        return $this->hasMany(Person::class);
    }

    /**
     * Get all of the organization emails.
     */
    public function emails()
    {
        return $this->morphMany(Email::class, 'emailable');
    }

    /**
     * Get all of the organization phone numbers.
     */
    public function phones()
    {
        return $this->morphMany(Phone::class, 'phoneable');
    }

    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function getBillingAddress()
    {
        return $this->addresses()->where('address_type_id', 5)->first();
    }

    public function getShippingAddress()
    {
        return $this->addresses()->where('address_type_id', 6)->first();
    }

    public function deals()
    {
        return $this->hasMany(Deal::class);
    }

    /**
     * Get all of the labels for the lead.
     */
    public function labels()
    {
        return $this->morphToMany(Label::class, config('laravel-crm.db_table_prefix').'labelable');
    }

    public function organizationType()
    {
        return $this->belongsTo(OrganizationType::class);
    }

    public function contacts()
    {
        return $this->morphMany(Contact::class, 'contactable');
    }

    /**
     * Get the xero contact associated with the organization.
     */
    public function xeroContact()
    {
        return $this->hasOne(XeroContact::class);
    }

    public function client()
    {
        return $this->morphOne(Customer::class, 'clientable');
    }

    public function timezone()
    {
        return $this->belongsTo(Timezone::class);
    }
}
