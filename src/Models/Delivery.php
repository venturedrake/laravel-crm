<?php

namespace VentureDrake\LaravelCrm\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasCrmActivities;
use VentureDrake\LaravelCrm\Traits\HasGlobalSettings;
use VentureDrake\LaravelCrm\Traits\SearchFilters;

class Delivery extends Model
{
    use SoftDeletes;
    use BelongsToTeams;
    use SearchFilters;
    use HasCrmActivities;
    use HasGlobalSettings;

    protected $guarded = ['id'];

    protected $filterable = [
        'user_owner_id',
    ];

    public function getSearchable()
    {
        return $this->searchable;
    }

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'deliveries';
    }

    public function getTitleAttribute()
    {
        if ($this->order) {
            return money($this->order->total, $this->order->currency).' - '.($this->order->client->name ?? $this->order->organisation->name ?? $this->order->organisation->person->name ?? null);
        }
    }

    public function setDeliveryExpectedAttribute($value)
    {
        if ($value) {
            $this->attributes['delivery_expected'] = Carbon::createFromFormat($this->dateFormat(), $value);
        }
    }

    public function getDeliveryExpectedAttribute($value)
    {
        if ($value) {
            return Carbon::parse($value)->format($this->dateFormat());
        }
    }

    public function setDeliveredOnAttribute($value)
    {
        if ($value) {
            $this->attributes['delivered_on'] = Carbon::createFromFormat($this->dateFormat(), $value);
        }
    }

    public function getDeliveredOnAttribute($value)
    {
        if ($value) {
            return Carbon::parse($value)->format($this->dateFormat());
        }
    }

    public function order()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\Order::class);
    }

    public function deliveryProducts()
    {
        return $this->hasMany(\VentureDrake\LaravelCrm\Models\DeliveryProduct::class);
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

    public function addresses()
    {
        return $this->morphMany(\VentureDrake\LaravelCrm\Models\Address::class, 'addressable');
    }

    public function getShippingAddress()
    {
        return $this->addresses()->where('address_type_id', 6)->first();
    }
}
