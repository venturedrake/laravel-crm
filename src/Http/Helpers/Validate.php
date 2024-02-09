<?php

namespace VentureDrake\LaravelCrm\Http\Helpers\Validate;

function email($email)
{
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $address = $email;
        $atPos = mb_strpos($address, '@');
        $domain = mb_substr($address, $atPos + 1);

        if(checkdnsrr($domain . '.', 'MX')) {
            return true;
        }
    }
}
