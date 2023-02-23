<?php

namespace VentureDrake\LaravelCrm\Repositories;

use VentureDrake\LaravelCrm\Models\Delivery;

class DeliveryRepository
{
    public function all()
    {
        return Delivery::all();
    }

    public function find($id)
    {
        return Delivery::find($id);
    }
}
