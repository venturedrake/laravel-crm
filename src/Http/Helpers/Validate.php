<?php

namespace VentureDrake\LaravelCrm\Http\Helpers\Validate;

function validEmail($email): bool
{
    if (empty($email)) {
        return false;
    }

    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validPhone($phone): bool
{
    if (empty($phone)) {
        return false;
    }

    return (bool) preg_match('/^\+?[0-9]{7,15}$/', $phone);
}

function validUrl($url): bool
{
    if (empty($url)) {
        return false;
    }

    return (bool) filter_var($url, FILTER_VALIDATE_URL);
}

function email($email)
{
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $address = $email;
        $atPos = mb_strpos($address, '@');
        $domain = mb_substr($address, $atPos + 1);

        if (checkdnsrr($domain.'.', 'MX')) {
            return true;
        }
    }
}
