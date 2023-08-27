<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasCrmActivities;
use VentureDrake\LaravelCrm\Traits\HasCrmFields;
use VentureDrake\LaravelCrm\Traits\SearchFilters;

class Order extends Model
{
    use SoftDeletes;
    use HasCrmFields;
    use BelongsToTeams;
    use SearchFilters;
    use HasCrmActivities;

    protected $guarded = ['id'];

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
        return config('laravel-crm.db_table_prefix').'orders';
    }

    public function getOrderIdAttribute($value)
    {
        if ($value) {
            return $value;
        } else {
            return (Setting::where('name', 'order_prefix')->first()->value ?? null) . $this->number;
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
        return money($this->total, $this->currency).' - '.($this->client->name ?? $this->organisation->name ?? $this->organisation->person->name ?? null);
    }

    public function setSubtotalAttribute($value)
    {
        if (isset($value)) {
            $this->attributes['subtotal'] = $value * 100;
        } else {
            $this->attributes['subtotal'] = null;
        }
    }

    public function setDiscountAttribute($value)
    {
        if (isset($value)) {
            $this->attributes['discount'] = $value * 100;
        } else {
            $this->attributes['discount'] = null;
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

    public function setAdjustmentsAttribute($value)
    {
        if (isset($value)) {
            $this->attributes['adjustments'] = $value * 100;
        } else {
            $this->attributes['adjustments'] = null;
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

    public function client()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\Client::class);
    }

    public function orderProducts()
    {
        return $this->hasMany(\VentureDrake\LaravelCrm\Models\OrderProduct::class);
    }

    public function deal()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\Deal::class);
    }

    public function quote()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\Quote::class);
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

    public function invoices()
    {
        return $this->hasMany(\VentureDrake\LaravelCrm\Models\Invoice::class);
    }

    public function deliveries()
    {
        return $this->hasMany(\VentureDrake\LaravelCrm\Models\Delivery::class);
    }

    public function addresses()
    {
        return $this->morphMany(\VentureDrake\LaravelCrm\Models\Address::class, 'addressable');
    }

    public function getBillingAddress()
    {
        return $this->addresses()->where('address_type_id', 5)->first();
    }

    public function getShippingAddress()
    {
        return $this->addresses()->where('address_type_id', 6)->first();
    }

    public function invoiceComplete()
    {
        foreach ($this->orderProducts as $orderProduct) {
            $quantity = $orderProduct->quantity;

            foreach ($this->invoices as $invoice) {
                if ($invoiceLine = $invoice->invoiceLines()->where('order_product_id', $orderProduct->id)->first()) {
                    $quantity -= $invoiceLine->quantity;
                }
            }

            if ($quantity > 0) {
                return false;
            }
        }

        return true;
    }

    public function deliveryComplete()
    {
        foreach ($this->orderProducts as $orderProduct) {
            $quantity = $orderProduct->quantity;

            foreach ($this->deliveries as $delivery) {
                if ($deliveryProduct = $delivery->deliveryProducts()->where('order_product_id', $orderProduct->id)->first()) {
                    $quantity -= $deliveryProduct->quantity;
                }
            }

            if ($quantity > 0) {
                return false;
            }
        }

        return true;
    }
}
