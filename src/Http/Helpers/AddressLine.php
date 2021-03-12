<?php

namespace VentureDrake\LaravelCrm\Http\Helpers\AddressLine;

function addressSingleLine($address)
{
    if ($address->line) {
        return $address->line;
    } else {
        $line = $address->line1;
        if ($address->line2) {
            $line .= ', '.$address->line2;
        }
        if ($address->line3) {
            $line .= ', '.$address->line3;
        }

        if ($address->city) {
            $line .= ', '.$address->city;
        }

        if ($address->state) {
            $line .= ', '.$address->state;
        }

        if ($address->code) {
            $line .= ' '.$address->code;
        }

        if ($address->country) {
            $line .= ' '.$address->country;
        }
    }

    return $line;
}
