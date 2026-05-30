<?php

namespace VentureDrake\LaravelCrm\Http\Helpers\ConvertCase;

function numbersFromString($string)
{
    $integer = preg_replace('/[^A-Za-z0-9\-]/', '', $string);
    $integer = preg_replace('/\s+/', '', $integer);

    return $integer;
}

function floatfromNumberFormat($number)
{
    if ($number) {
        return (float) str_replace(',', '', $number);
    }

    return 0;
}

function integerfromNumberFormat($number)
{
    return (int) str_replace(',', '', $number);
}

function floatfromCurrencyFormat($number)
{
    return \VentureDrake\LaravelCrm\Http\Helpers\ConvertCase\floatfromNumberFormat(str_replace('$', '', $number));
}
