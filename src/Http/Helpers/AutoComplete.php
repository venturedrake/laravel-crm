<?php

namespace VentureDrake\LaravelCrm\Http\Helpers\AutoComplete;

use VentureDrake\LaravelCrm\Models\Client;
use VentureDrake\LaravelCrm\Models\Organization;
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

function clientsWithDetails()
{
    $data = [];

    foreach (Client::all() as $client) {
        $label = '<strong>'.$client->name.'</strong>';

        if ($contacts = $client->contacts()->get()) {
            $label .= '<br />';

            foreach ($contacts as $key => $contact) {
                $label .= '<small>'.$contact->entityable->name.'</small>';
                if ($contacts->last() != $contact) {
                    $label .= ', ';
                }
            }
        }

        $data[] = [
            'value' => $client->id,
            'label' => $label,
            'name' => $client->name,
        ];
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

function peopleWithDetails()
{
    $data = [];

    foreach (Person::all() as $person) {
        $label = '<strong>'.$person->name.'</strong>';

        if ($email = $person->getPrimaryEmail()) {
            $label .= '<br />';
            $label .= ' <small>'.$email->address.'</small>';
        }

        if ($address = $person->getPrimaryAddress()) {
            $label .= '<br />';
            $label .= ' <small>'.$address->state.', '.$address->code.'</small>';
        }

        $data[] = [
            'value' => $person->id,
            'label' => $label,
            'name' => $person->name,
        ];
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

function organisationsWithDetails()
{
    $data = [];

    foreach (Organisation::all() as $organisation) {
        if ($organisation->xeroContact) {
            $label = '<strong>'.$organisation->name. '</strong> (xero contact)';

            if ($contacts = $organisation->contacts()->get()) {
                $label .= '<br />';

                foreach ($contacts as $key => $contact) {
                    $label .= '<small>'.$contact->entityable->name.'</small>';
                    if (end($contacts) != $key) {
                        $label .= ', ';
                    }
                }
            }

            $data[] = [
                'value' => $organisation->id,
                'label' => $label,
                'name' => $organisation->name,
            ];
        } else {
            $label = '<strong>'.$organisation->name. '</strong>';

            if ($contacts = $organisation->contacts()->get()) {
                $label .= '<br />';

                foreach ($contacts as $key => $contact) {
                    $label .= '<small>'.$contact->entityable->name.'</small>';
                    if (end($contacts) != $key) {
                        $label .= ', ';
                    }
                }
            }

            $data[] = [
                'value' => $organisation->id,
                'label' => $label,
                'name' => $organisation->name,
            ];
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
