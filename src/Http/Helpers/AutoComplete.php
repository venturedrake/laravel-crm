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

function organisationsSelect2()
{
    $data = [];

    foreach (Organisation::all() as $organisation) {
        $data[] = [
            'id' => $organisation->id,
            'text' => $organisation->name,
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
