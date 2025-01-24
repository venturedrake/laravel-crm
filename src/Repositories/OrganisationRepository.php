<?php

namespace VentureDrake\LaravelCrm\Repositories;

use VentureDrake\LaravelCrm\Models\Organization;

class OrganisationRepository
{
    public function all()
    {
        return Organization::all();
    }

    public function find($id)
    {
        return Organization::find($id);
    }
}
