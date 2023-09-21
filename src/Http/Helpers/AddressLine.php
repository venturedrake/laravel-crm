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

function addressMultipleLines($address)
{
    $lines = $address->line1;

    if ($address->line2) {
        $lines .= PHP_EOL.$address->line2;
    }
    if ($address->line3) {
        $lines .= PHP_EOL.$address->line3;
    }
    if ($address->city) {
        $lines .= PHP_EOL.$address->city;
    }

    if ($address->state) {
        $lines .= ' '.$address->state;
    }

    if ($address->code) {
        $lines .= ' '.$address->code;
    }

    if ($address->country) {
        $lines .= PHP_EOL.$address->country;
    }

    return $lines;
}
