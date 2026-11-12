<?php

namespace VentureDrake\LaravelCrm\Models;

use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Support\Modules;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasCrmActivities;
use VentureDrake\LaravelCrm\Traits\HasCrmFields;
use VentureDrake\LaravelCrm\Traits\HasEncryptableFields;
use VentureDrake\LaravelCrm\Traits\HasGlobalSettings;
use VentureDrake\LaravelCrm\Traits\HasLinkedRecordsWarning;
use VentureDrake\LaravelCrm\Traits\HasPrimaryContactDetails;
use VentureDrake\LaravelCrm\Traits\SearchFilters;

class Person extends Model
{
    use BelongsToTeams;
    use HasCrmActivities;
    use HasCrmFields;
    use HasEncryptableFields;
    use HasGlobalSettings;
    use HasLinkedRecordsWarning;
    use HasPrimaryContactDetails;
    use SearchFilters;
    use SoftDeletes;

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

    protected $casts = [
        'birthday' => 'date',
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

    /*public function setBirthdayAttribute($value)
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
    }*/

    public function getFirstNameDecryptedAttribute()
    {
        return $this->decryptField($this->first_name);
    }

    /**
     * Get all of the persons emails.
     */
    public function emails()
    {
        return $this->morphMany(Email::class, 'emailable');
    }

    /**
     * Get all of the persons phone numbers.
     */
    public function phones()
    {
        return $this->morphMany(Phone::class, 'phoneable');
    }

    /**
     * Get all of the leads addresses.
     */
    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function deals()
    {
        return $this->hasMany(Deal::class);
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * The records that point at this person, listed in the delete
     * dialog. They keep the reference once it is deleted.
     */
    public function linkedRecordRelations(): array
    {
        return ['leads', 'deals', 'quotes', 'orders', 'invoices', 'purchaseOrders'];
    }

    /**
     * Every position the person holds or has held, newest first.
     */
    public function positions()
    {
        return $this->hasMany(Position::class)->orderByDesc('start_date')->orderByDesc('id');
    }

    /**
     * The positions the person holds today.
     */
    public function currentPositions()
    {
        return $this->positions()->current();
    }

    /**
     * The person's current position titles as one string ("CFO, Director"),
     * or null when they hold none or the positions module is disabled.
     */
    public function getCurrentPositionTitleAttribute(): ?string
    {
        if (! Modules::enabled('positions')) {
            return null;
        }

        return $this->currentPositions->pluck('title')->unique()->implode(', ') ?: null;
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

    /**
     * Get all of the labels for the person.
     */
    public function labels()
    {
        return $this->morphToMany(Label::class, config('laravel-crm.db_table_prefix').'labelable');
    }

    public function contacts()
    {
        return $this->morphMany(Contact::class, 'contactable');
    }

    /**
     * Get the xero person associated with the person.
     */
    public function xeroPerson()
    {
        return $this->hasOne(XeroPerson::class);
    }

    public function client()
    {
        return $this->morphOne(Customer::class, 'clientable');
    }
}
