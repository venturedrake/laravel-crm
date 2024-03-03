<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

class XeroPurchaseOrder extends Model
{
    use SoftDeletes;
    use BelongsToTeams;

    protected $guarded = ['id'];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'xero_purchase_orders';
    }

    /**
     * Get the invoice that owns the xero invoice.
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\PurchaseOrder::class);
    }
}
