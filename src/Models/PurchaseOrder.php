<?php

namespace VentureDrake\LaravelCrm\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasCrmActivities;
use VentureDrake\LaravelCrm\Traits\HasCrmFields;
use VentureDrake\LaravelCrm\Traits\HasGlobalSettings;
use VentureDrake\LaravelCrm\Traits\SearchFilters;

class PurchaseOrder extends Model
{
    use SoftDeletes;
    use HasCrmFields;
    use BelongsToTeams;
    use SearchFilters;
    use HasCrmActivities;
    use HasGlobalSettings;

    protected $guarded = ['id'];

    protected $casts = [
        'issue_date' => 'datetime',
        'delivery_date' => 'datetime',
    ];

    protected $searchable = [
        'reference',
        'order_id',
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
        return config('laravel-crm.db_table_prefix').'purchase_orders';
    }

    public function getPurchaseOrderIdAttribute($value)
    {
        if ($value) {
            return $value;
        } else {
            return (Setting::where('name', 'purchase_order_prefix')->first()->value ?? null) . $this->number;
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
        return money($this->total, $this->currency).' - '.($this->organisation->name ?? $this->person->name ?? null);
    }

    public function setIssueDateAttribute($value)
    {
        if ($value) {
            $this->attributes['issue_date'] = Carbon::createFromFormat($this->dateFormat(), $value);
        }
    }

    public function setDeliveryDateAttribute($value)
    {
        if ($value) {
            $this->attributes['delivery_date'] = Carbon::createFromFormat($this->dateFormat(), $value);
        }
    }

    public function setSubtotalAttribute($value)
    {
        if (isset($value)) {
            $this->attributes['subtotal'] = $value * 100;
        } else {
            $this->attributes['subtotal'] = null;
        }
    }

    public function setTaxAttribute($value)
    {
        if (isset($value)) {
            $this->attributes['tax'] = $value * 100;
        } else {
            $this->attributes['tax'] = null;
        }
    }

    public function setTotalAttribute($value)
    {
        if (isset($value)) {
            $this->attributes['total'] = $value * 100;
        } else {
            $this->attributes['total'] = null;
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

    public function order()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\Order::class);
    }

    public function purchaseOrderLines()
    {
        return $this->hasMany(\VentureDrake\LaravelCrm\Models\PurchaseOrderLine::class);
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

    /**
     * Get the xero purchase order associated with the purchase order.
     */
    public function xeroPurchaseOrder()
    {
        return $this->hasOne(\VentureDrake\LaravelCrm\Models\XeroPurchaseOrder::class);
    }

    public function address()
    {
        return $this->morphOne(\VentureDrake\LaravelCrm\Models\Address::class, 'addressable');
    }
}
