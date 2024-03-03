<?php

namespace VentureDrake\LaravelCrm\Repositories;

use VentureDrake\LaravelCrm\Models\PurchaseOrder;

class PurchaseOrderRepository
{
    public function all()
    {
        return PurchaseOrder::all();
    }

    public function find($id)
    {
        return PurchaseOrder::find($id);
    }
}
