<?php

namespace VentureDrake\LaravelCrm\Http\Helpers\AutoComplete;

use VentureDrake\LaravelCrm\Models\Customer;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Product;

function clients()
{
    $data = [];

    foreach (Customer::all() as $client) {
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

function organizations()
{
    $data = [];

    foreach (Organization::all() as $organization) {
        if ($organization->xeroContact) {
            $data[$organization->name.' (xero contact)'] = $organization->id;
        } else {
            $data[$organization->name] = $organization->id;
        }
    }

    return json_encode($data);
}

function organizationsSelect2()
{
    $data = [];

    foreach (Organization::all() as $organization) {
        $data[] = [
            'id' => $organization->id,
            'text' => $organization->name,
        ];
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
