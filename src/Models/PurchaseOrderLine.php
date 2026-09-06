<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Support\Money;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasDecimalQuantity;

class PurchaseOrderLine extends Model
{
    use BelongsToTeams;
    use HasDecimalQuantity;
    use SoftDeletes;

    protected $guarded = ['id'];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'purchase_order_lines';
    }

    public function setPriceAttribute($value)
    {
        $this->attributes['price'] = Money::toInteger($value);
    }

    public function setTaxAmountAttribute($value)
    {
        $this->attributes['tax_amount'] = Money::toInteger($value);
    }

    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = Money::toInteger($value);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class)->withTrashed();
    }
}
