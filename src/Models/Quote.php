<?php

namespace VentureDrake\LaravelCrm\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasCrmActivities;
use VentureDrake\LaravelCrm\Traits\HasCrmFields;
use VentureDrake\LaravelCrm\Traits\HasGlobalSettings;
use VentureDrake\LaravelCrm\Traits\SearchFilters;

class Quote extends Model
{
    use SoftDeletes;
    use HasCrmFields;
    use BelongsToTeams;
    use SearchFilters;
    use HasCrmActivities;
    use HasGlobalSettings;

    protected $guarded = ['id'];

    protected $casts = [
        'issue_at' => 'datetime',
        'expire_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    protected $searchable = [
        'title',
        'reference',
        'quote_id',
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
        return config('laravel-crm.db_table_prefix').'quotes';
    }

    public function getQuoteIdAttribute($value)
    {
        if ($value) {
            return $value;
        } else {
            return (Setting::where('name', 'quote_prefix')->first()->value ?? null) . $this->number;
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

    public function setIssueAtAttribute($value)
    {
        if ($value) {
            $this->attributes['issue_at'] = Carbon::createFromFormat($this->dateFormat(), $value);
        }
    }

    public function setExpireAtAttribute($value)
    {
        if ($value) {
            $this->attributes['expire_at'] = Carbon::createFromFormat($this->dateFormat(), $value);
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

    public function quoteProducts()
    {
        return $this->hasMany(\VentureDrake\LaravelCrm\Models\QuoteProduct::class);
    }

    public function deal()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\Deal::class);
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

    public function orders()
    {
        return $this->hasMany(\VentureDrake\LaravelCrm\Models\Order::class);
    }

    public function orderComplete()
    {
        foreach ($this->quoteProducts as $quoteProduct) {
            $quantity = $quoteProduct->quantity;

            foreach ($this->orders as $order) {
                if ($orderProduct = $order->orderProducts()->where('quote_product_id', $quoteProduct->id)->first()) {
                    $quantity -= $orderProduct->quantity;
                }
            }

            if ($quantity > 0) {
                return false;
            }
        }

        return true;
    }
}
