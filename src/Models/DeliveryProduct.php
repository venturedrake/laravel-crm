<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasDecimalQuantity;

class DeliveryProduct extends Model
{
    use BelongsToTeams;
    use HasDecimalQuantity;
    use SoftDeletes;

    protected $guarded = ['id'];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'delivery_products';
    }

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class)->withTrashed();
    }

    /**
     * A delivery line has no product of its own - it prints the order line it
     * draws down, variation included.
     */
    public function displayName(): string
    {
        return $this->orderProduct?->displayName() ?? '';
    }

    public function displayCode(): ?string
    {
        return $this->orderProduct?->displayCode();
    }
}
