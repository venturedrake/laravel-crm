<?php

namespace VentureDrake\LaravelCrm\Repositories;

use VentureDrake\LaravelCrm\Models\Person;

class PersonRepository
{
    public function all()
    {
        return Person::all();
    }

    public function find($id)
    {
        return Person::find($id);
    }
}
