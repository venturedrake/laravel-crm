<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

class ProductPrice extends Model
{
    use SoftDeletes;
    use BelongsToTeams;

    protected $guarded = ['id'];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'product_prices';
    }

    public function setUnitPriceAttribute($value)
    {
        if (isset($value)) {
            $this->attributes['unit_price'] = $value * 100;
        } else {
            $this->attributes['unit_price'] = null;
        }
    }

    public function setCostPerUnitAttribute($value)
    {
        if (isset($value)) {
            $this->attributes['cost_per_unit'] = $value * 100;
        } else {
            $this->attributes['cost_per_unit'] = null;
        }
    }

    public function setDirectCostAttribute($value)
    {
        if (isset($value)) {
            $this->attributes['direct_cost'] = $value * 100;
        } else {
            $this->attributes['direct_cost'] = null;
        }
    }

    public function product()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\Product::class);
    }

    public function productVariation()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\ProductVariation::class);
    }

    public function scopeDefault($query)
    {
        return $query->where('currency', Setting::currency() ?? 'USD');
    }
}
