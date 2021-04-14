<?php

namespace VentureDrake\LaravelCrm\Repositories;

use VentureDrake\LaravelCrm\Models\Lead;

class LeadRepository
{
    public function all()
    {
        return Lead::all();
    }

    public function find($id)
    {
        return Lead::find($id);
    }
}
