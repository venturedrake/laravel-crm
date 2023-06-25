<?php

namespace VentureDrake\LaravelCrm\Http\Helpers\SelectOptions;

use App\User;
use Carbon\Carbon;
use Rinvex\Country\CountryLoader;
use Rinvex\Country\CurrencyLoader;
use VentureDrake\LaravelCrm\Models\Timezone;

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

    $users = [];

    if (config('laravel-crm.teams')) {
        if (auth()->user()->currentTeam) {
            $users = auth()->user()->currentTeam->allUsers();
        }
    } else {
        $users = User::all();
    }

    foreach ($users as $item) {
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
        'fax' => 'Fax',
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

function countries()
{
    /* $countries = new Countries();
     $items = [];

     foreach ($countries->all()->pluck('name.common')->toArray() as $country) {
         $items[$country] = $country;
     }*/

    foreach (CountryLoader::countries() as $country) {
        $items[$country['name']] = $country['name'];
    }

    return $items;
}

function currencies()
{
    /*$countries = new Countries();
    $items = [];
    foreach ($countries->currencies()->sortBy('name')->toArray() as $currencyCode => $currency) {
        $items[$currencyCode] = $currency['name'].(' ('.$currencyCode.')');
    }*/

    // Allow for typo in package
    if (method_exists('Rinvex\Country\CurrencyLoader', 'curriencies')) {
        foreach (CurrencyLoader::curriencies(true) as $currency) {
            $items[$currency['iso_4217_code']] = $currency['iso_4217_name'].(' ('.$currency['iso_4217_code'].')');
        }
    } else {
        foreach (CurrencyLoader::currencies(true) as $currency) {
            $items[$currency['iso_4217_code']] = $currency['iso_4217_name'].(' ('.$currency['iso_4217_code'].')');
        }
    }

    return $items;
}


function timezones()
{
    $items = [];

    $items[''] = '';

    foreach (Timezone::all() as $timezone) {
        $items[$timezone->name] = $timezone->name.' - '.$timezone->diff_from_gtm;
    }

    return $items;
}

function dateFormats()
{
    $now = Carbon::now();

    return [
        'j F Y' => $now->format('j F Y'),
        'Y-m-d' => $now->format('Y-m-d'),
        'Y/m/d' => $now->format('Y/m/d'),
        'm/d/Y' => $now->format('m/d/Y'),
        'd/m/Y' => $now->format('d/m/Y'),
        'F j, Y' => $now->format('F j, Y'),
        'M j, Y' => $now->format('M j, Y'),
    ];
}

function timeFormats()
{
    $now = Carbon::now();

    return [
        'g:i a' => $now->format('g:i a'),
        'g:i A' => $now->format('g:i A'),
        'H:i' => $now->format('H:i'),
        'H:i:s' => $now->format('H:i:s'),
    ];
}

function fieldModels()
{
    return [
        'VentureDrake\LaravelCrm\Models\Lead' => ucfirst(__('laravel-crm::lang.leads')),
        'VentureDrake\LaravelCrm\Models\Deal' => ucfirst(__('laravel-crm::lang.deals')),
        'VentureDrake\LaravelCrm\Models\Quote' => ucfirst(__('laravel-crm::lang.quotes')),
        'VentureDrake\LaravelCrm\Models\Order' => ucfirst(__('laravel-crm::lang.orders')),
        'VentureDrake\LaravelCrm\Models\Person' => ucfirst(__('laravel-crm::lang.people')),
        'VentureDrake\LaravelCrm\Models\Organisation' => ucfirst(__('laravel-crm::lang.organizations')),
        'VentureDrake\LaravelCrm\Models\Product' => ucfirst(__('laravel-crm::lang.products')),
    ];
}
