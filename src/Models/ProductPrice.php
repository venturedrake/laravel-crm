<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductPrice extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'product_prices';
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
