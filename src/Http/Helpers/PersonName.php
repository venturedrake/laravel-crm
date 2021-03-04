<?php

namespace VentureDrake\LaravelCrm\Http\Helpers\PersonName;

function firstLastFromName($name)
{
    $parts = explode(' ', $name);
    $lastname = trim(array_pop($parts));
    $firstname = trim(implode(' ', $parts));

    return [
        'first_name' => $firstname,
        'last_name' => $lastname,
    ];
}

function firstNameFromName($name)
{
    $nameArray = \VentureDrake\LaravelCrm\Http\Helpers\PersonName\firstLastFromName($name);

    if ($nameArray['first_name']) {
        return $nameArray['first_name'];
    }
}
