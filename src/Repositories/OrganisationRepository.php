<?php

namespace VentureDrake\LaravelCrm\Repositories;

use VentureDrake\LaravelCrm\Models\Organisation;

class OrganisationRepository
{
    public function all()
    {
        return Organisation::all();
    }

    public function find($id)
    {
        return Organisation::find($id);
    }
}
