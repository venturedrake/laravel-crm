<?php

namespace VentureDrake\LaravelCrm\Support;

use Illuminate\Support\Facades\URL;

class CampaignClickLink
{
    public static function signedUrl(string $route, string $token, string $destination): string
    {
        return URL::signedRoute($route, [
            'token' => $token,
            'u' => rtrim(strtr(base64_encode($destination), '+/', '-_'), '='),
        ]);
    }

    public static function destination(mixed $encoded): ?string
    {
        if (! is_string($encoded) || ! preg_match('/^[A-Za-z0-9_-]+$/D', $encoded)) {
            return null;
        }

        $url = base64_decode(strtr($encoded, '-_', '+/'), true);

        if ($url === false || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? $url : null;
    }
}
