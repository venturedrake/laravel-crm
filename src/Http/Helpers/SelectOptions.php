<?php

namespace VentureDrake\LaravelCrm\Http\Helpers\SelectOptions;

use PragmaRX\Countries\Package\Countries;

function optionsFromModel($model, $null = true)
{
    $array = [];

    if ($null) {
        $array[''] = '';
    }

    if ($model) {
        foreach ($model as $item) {
            $array[$item->id] = $item->name;
        }
    }

    return $array;
}

function users($null = true)
{
    $array = [];

    if ($null) {
        $array[''] = '';
    }

    foreach (\App\User::all() as $item) {
        $array[$item->id] = $item->name;
    }

    return $array;
}

function phoneTypes($null = true)
{
    $array = [];
    
    if ($null) {
        $array[''] = '';
    }

    $array = array_merge($array, [
        'work' => 'Work',
        'home' => 'Home',
        'mobile' => 'Mobile',
        'other' => "Other",
    ]);
    
    return $array;
}

function emailTypes($null = true)
{
    $array = [];

    if ($null) {
        $array[''] = '';
    }

    $array = array_merge($array, [
        'work' => 'Work',
        'home' => 'Home',
        'other' => "Other",
    ]);

    return $array;
}

function currencies()
{
    $countries = new Countries();
    $items = [];
    foreach ($countries->currencies()->toArray() as $currencyCode => $currency) {
        $items[$currencyCode] = $currency['name'].(' ('.$currencyCode.')');
    }

    return $items;
}
