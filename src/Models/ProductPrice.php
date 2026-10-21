<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Support\Money;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

class ProductPrice extends Model
{
    use BelongsToTeams;
    use SoftDeletes;

    protected $guarded = ['id'];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'product_prices';
    }

    public function setUnitPriceAttribute($value)
    {
        $this->attributes['unit_price'] = Money::toInteger($value);
    }

    public function setCostPerUnitAttribute($value)
    {
        $this->attributes['cost_per_unit'] = Money::toInteger($value);
    }

    public function setDirectCostAttribute($value)
    {
        $this->attributes['direct_cost'] = Money::toInteger($value);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class);
    }

    /**
     * Product-level prices in the install currency - the rows
     * Product::getDefaultPrice() reads. Variation prices are excluded.
     */
    public function scopeDefault($query)
    {
        return $query->where('currency', app('laravel-crm.settings')->get('currency') ?? 'USD')
            ->whereNull('product_variation_id');
    }
}
