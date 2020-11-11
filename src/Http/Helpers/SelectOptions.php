<?php

namespace App\Http\Helpers;

function phoneTypes(){
    return [
        'work',
        'home',
        'mobile',
        'other',
    ];
}

function emailTypes(){
    return [
        'work',
        'home',
        'other',
    ];
}

function optionsFromModel($model, $null = true, $default = null, $append = null){
    $array = [];

    if ($null) {
        $array[''] = '';
    }

    if ($model) {
        foreach ($model as $item) {
            $array[$item->id] = $item->name;
        }
    }

    if ($append) {
        $array += $append;
    }

    return $array;
}