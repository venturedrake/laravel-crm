<?php

namespace VentureDrake\LaravelCrm\Http\Helpers\AutoComplete;

use VentureDrake\LaravelCrm\Models\Client;
use VentureDrake\LaravelCrm\Models\Organisation;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Product;

function clients()
{
    $data = [];

    foreach (Client::all() as $client) {
        $data[$client->name] = $client->id;
    }

    return json_encode($data);
}

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
        if ($organisation->xeroContact) {
            $data[$organisation->name . ' (xero contact)'] = $organisation->id;
        } else {
            $data[$organisation->name] = $organisation->id;
        }
    }

    return json_encode($data);
}

function products()
{
    $data = [];

    foreach (Product::all() as $product) {
        $data[$product->name] = $product->id;
    }

    return json_encode($data);
}

function productsSelect2()
{
    $data = [];

    /*$data[] = [
        'id' => -1,
        'text' => null,
    ];*/

    foreach (Product::all() as $product) {
        $data[] = [
            'id' => $product->id,
            'text' => $product->name,
        ];
    }

    return json_encode($data);
}
