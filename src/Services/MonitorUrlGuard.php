<?php

namespace VentureDrake\LaravelCrm\Services;

class MonitorUrlGuard
{
    /**
     * Reject monitor targets that would let an admin probe internal services
     * (SSRF). Validates the URL scheme and resolves the host so we can refuse
     * loopback, link-local, private, or otherwise reserved IPs *before* the
     * outbound request is issued.
     */
    public static function reasonForRejection(?string $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return 'URL is required.';
        }

        $parts = parse_url($url);

        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return 'URL must include a scheme and host.';
        }

        $scheme = strtolower($parts['scheme']);

        if (! in_array($scheme, ['http', 'https'], true)) {
            return 'URL scheme must be http or https.';
        }

        if (config('laravel-crm.monitoring.allow_private_targets')) {
            return null;
        }

        $host = $parts['host'];

        foreach (self::resolveHost($host) as $ip) {
            if (! self::isPublicIp($ip)) {
                return 'URL host resolves to a non-routable address.';
            }
        }

        return null;
    }

    public static function isAllowed(?string $url): bool
    {
        return self::reasonForRejection($url) === null;
    }

    /**
     * @return array<int, string>
     */
    private static function resolveHost(string $host): array
    {
        $host = trim($host, '[]');

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $records = @dns_get_record($host, DNS_A + DNS_AAAA);

        if (! is_array($records) || $records === []) {
            // If DNS resolution fails we'd rather error out the check itself
            // than silently let it through. Returning a sentinel reserved IP
            // forces rejection above.
            return ['127.0.0.1'];
        }

        $ips = [];

        foreach ($records as $record) {
            if (isset($record['ip'])) {
                $ips[] = $record['ip'];
            } elseif (isset($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }

        return $ips;
    }

    private static function isPublicIp(string $ip): bool
    {
        return (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
