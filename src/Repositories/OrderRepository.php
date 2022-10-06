<?php

namespace VentureDrake\LaravelCrm\Repositories;

use VentureDrake\LaravelCrm\Models\Order;

class OrderRepository
{
    public function all()
    {
        return Order::all();
    }

    public function find($id)
    {
        return Order::find($id);
    }
}
