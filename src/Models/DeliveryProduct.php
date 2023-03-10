<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

class DeliveryProduct extends Model
{
    use SoftDeletes;
    use BelongsToTeams;

    protected $guarded = ['id'];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'delivery_products';
    }

    public function delivery()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\Delivery::class);
    }

    public function orderProduct()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\OrderProduct::class);
    }
}
