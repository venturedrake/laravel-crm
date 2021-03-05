<?php

namespace VentureDrake\LaravelCrm\Http\Helpers\AutoComplete;

use VentureDrake\LaravelCrm\Models\Organisation;
use VentureDrake\LaravelCrm\Models\Person;

function people()
{
    $data = [];
    
    foreach (Person::all() as $person) {
        $data[$person->name] = $person->id;
    }

    return json_encode($data);
}

function organisations()
{
    $data = [];

    foreach (Organisation::all() as $organisation) {
        $data[$organisation->name] = $organisation->id;
    }

    return json_encode($data);
}
