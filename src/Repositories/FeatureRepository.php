<?php

namespace VentureDrake\LaravelCrm\Repositories;

use VentureDrake\LaravelCrm\Models\Feature;

class FeatureRepository
{
    public function all()
    {
        return Feature::all();
    }

    public function find($id)
    {
        return Feature::find($id);
    }
}
