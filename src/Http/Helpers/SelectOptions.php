<?php

namespace VentureDrake\LaravelCrm\Http\Helpers;

function phoneTypes($null = true)
{
    $array = [];
    
    if ($null) {
        $array[''] = '';
    }

    $array = array_merge($array, [
        'work' => 'work',
        'home' => 'home',
        'mobile' => 'mobile',
        'other' => "other",
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
        'work' => 'work',
        'home' => 'home',
        'other' => "other",
    ]);

    return $array;
}

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
