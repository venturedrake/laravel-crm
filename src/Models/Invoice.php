<?php

namespace VentureDrake\LaravelCrm\Models;

use App\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Support\Money;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasCrmActivities;
use VentureDrake\LaravelCrm\Traits\HasCrmFields;
use VentureDrake\LaravelCrm\Traits\HasGlobalSettings;
use VentureDrake\LaravelCrm\Traits\SearchFilters;

class Invoice extends Model
{
    use BelongsToTeams;
    use HasCrmActivities;
    use HasCrmFields;
    use HasGlobalSettings;
    use SearchFilters;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'issue_date' => 'datetime',
        'due_date' => 'datetime',
        'fully_paid_at' => 'datetime',
    ];

    protected $searchable = [
        'reference',
        'invoice_id',
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
        return config('laravel-crm.db_table_prefix').'invoices';
    }

    public function getInvoiceIdAttribute($value)
    {
        if ($value) {
            return $value;
        } else {
            // Off the memoised settings map rather than a fresh
            // crm_settings query. This accessor runs for every row of an index
            // page and for every document a mail template renders.
            return app('laravel-crm.settings')->get('invoice_prefix').$this->number;
        }
    }

    public function getNumberAttribute($value)
    {
        if ($value) {
            return $value;
        } else {
            return $this->id;
        }
    }

    public function getTitleAttribute()
    {
        return money($this->total, $this->currency).' - '.($this->organization->name ?? $this->person->name ?? null);
    }

    public function setSubtotalAttribute($value)
    {
        $this->attributes['subtotal'] = Money::toInteger($value);
    }

    public function setTaxAttribute($value)
    {
        $this->attributes['tax'] = Money::toInteger($value);
    }

    public function setTotalAttribute($value)
    {
        $this->attributes['total'] = Money::toInteger($value);
    }

    public function setAmountDueAttribute($value)
    {
        $this->attributes['amount_due'] = Money::toInteger($value);
    }

    public function getAmountDueAttribute($value)
    {
        if ($value >= 0) {
            return $value;
        } else {
            return $this->total;
        }
    }

    public function setAmountPaidAttribute($value)
    {
        $this->attributes['amount_paid'] = Money::toInteger($value);
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function invoiceLines()
    {
        return $this->hasMany(InvoiceLine::class);
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

    /**
     * Get the xero invoice associated with the invoice.
     */
    public function xeroInvoice()
    {
        return $this->hasOne(XeroInvoice::class);
    }
}
