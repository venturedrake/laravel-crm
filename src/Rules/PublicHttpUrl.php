<?php

namespace VentureDrake\LaravelCrm\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PublicHttpUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('The :attribute must be a valid URL.');

            return;
        }

        $candidate = $value;

        if (! preg_match('#^[a-zA-Z][a-zA-Z0-9+.\-]*://#', $candidate)) {
            $candidate = 'https://'.ltrim($candidate, '/');
        }

        $parts = parse_url($candidate);

        if (! is_array($parts) || empty($parts['host']) || empty($parts['scheme'])) {
            $fail('The :attribute must be a valid URL with a host.');

            return;
        }

        $scheme = strtolower($parts['scheme']);

        if (! in_array($scheme, ['http', 'https'], true)) {
            $fail('The :attribute must use the http or https scheme.');

            return;
        }

        $host = strtolower($parts['host']);

        if ($this->hostIsBlocked($host)) {
            $fail('The :attribute must point to a publicly reachable address.');
        }
    }

    private function hostIsBlocked(string $host): bool
    {
        if (in_array($host, ['localhost', 'ip6-localhost', 'ip6-loopback'], true)) {
            return true;
        }

        $ips = [];

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips[] = $host;
        } else {
            $records = @dns_get_record($host, DNS_A | DNS_AAAA);

            if (is_array($records)) {
                foreach ($records as $record) {
                    if (! empty($record['ip'])) {
                        $ips[] = $record['ip'];
                    } elseif (! empty($record['ipv6'])) {
                        $ips[] = $record['ipv6'];
                    }
                }
            }
        }

        if (empty($ips)) {
            // Host did not resolve — treat as blocked to fail closed.
            return true;
        }

        foreach ($ips as $ip) {
            if (! filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            )) {
                return true;
            }
        }

        return false;
    }
}
