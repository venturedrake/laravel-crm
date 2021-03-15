<?php

namespace VentureDrake\LaravelCrm\Repositories;

use VentureDrake\LaravelCrm\Models\Deal;

class DealRepository
{
    public function all()
    {
        return Deal::all();
    }

    public function find($id)
    {
        return Deal::find($id);
    }
}
