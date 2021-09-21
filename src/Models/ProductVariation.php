<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

class ProductVariation extends Model
{
    use SoftDeletes;
    use BelongsToTeams;

    protected $guarded = ['id'];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'product_variations';
    }

    public function product()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\Product::class);
    }

    public function productPrices()
    {
        return $this->hasMany(\VentureDrake\LaravelCrm\Models\ProductPrice::class);
    }
}
