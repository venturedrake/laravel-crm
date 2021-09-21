<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

class DealProduct extends Model
{
    use SoftDeletes;
    use BelongsToTeams;

    protected $guarded = ['id'];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'deal_products';
    }

    public function setPriceAttribute($value)
    {
        if (isset($value)) {
            $this->attributes['price'] = $value * 100;
        } else {
            $this->attributes['price'] = null;
        }
    }

    public function setAmountAttribute($value)
    {
        if (isset($value)) {
            $this->attributes['amount'] = $value * 100;
        } else {
            $this->attributes['amount'] = null;
        }
    }

    public function deal()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\Deal::class);
    }

    public function product()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\Product::class);
    }

    public function productVariation()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\ProductVariation::class);
    }
}
