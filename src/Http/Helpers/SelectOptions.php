<?php

namespace VentureDrake\LaravelCrm\Http\Helpers;

function phoneTypes($null = true){
    $array = [];
    
    if ($null) {
        $array[''] = '';
    }
    
    array_merge($array,[
        'work',
        'home',
        'mobile',
        'other',
    ]);
    
    return $array;
}

function emailTypes($null = true){
    return [
        'work',
        'home',
        'other',
    ];
}

function optionsFromModel($model, $null = true){
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