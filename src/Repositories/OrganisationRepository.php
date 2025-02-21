<?php

namespace VentureDrake\LaravelCrm\Repositories;

use VentureDrake\LaravelCrm\Models\Organization;

class OrganizationRepository
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
