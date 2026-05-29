<?php

namespace VentureDrake\LaravelCrm\Services;

use Carbon\Carbon;
use GuzzleHttp\TransferStats;
use Illuminate\Support\Facades\Http;
use Throwable;
use VentureDrake\LaravelCrm\Models\Monitor;

class MonitorCheckService
{
    public function checkUptime(Monitor $monitor): array
    {
        $result = [
            'status' => 'down',
            'status_code' => null,
            'response_time_ms' => null,
            'error' => null,
        ];

        if ($reason = MonitorUrlGuard::reasonForRejection($monitor->url)) {
            $result['error'] = $reason;

            return $result;
        }

        $timeout = (int) config('laravel-crm.monitoring.request_timeout_seconds', 15);

        $start = microtime(true);
        $transferSeconds = null;

        try {
            $response = Http::timeout($timeout)
                ->withOptions(['on_stats' => function (TransferStats $stats) use (&$transferSeconds) {
                    $transferSeconds = $stats->getTransferTime();
                }])
                ->get($monitor->url);

            $result['response_time_ms'] = $transferSeconds !== null
                ? (int) round($transferSeconds * 1000)
                : (int) round((microtime(true) - $start) * 1000);
            $result['status_code'] = $response->status();

            if ($response->successful()) {
                $threshold = $monitor->perf_threshold_ms ?? null;

                if ($threshold !== null && $result['response_time_ms'] > (int) $threshold) {
                    $result['status'] = 'slow';
                } else {
                    $result['status'] = 'up';
                }
            } else {
                $result['status'] = 'down';
                $result['error'] = 'HTTP '.$response->status();
            }
        } catch (Throwable $e) {
            $result['response_time_ms'] = (int) round((microtime(true) - $start) * 1000);
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    public function checkSsl(Monitor $monitor): array
    {
        $result = [
            'valid' => false,
            'issuer' => null,
            'expires_at' => null,
            'error' => null,
        ];

        $host = $monitor->host ?: parse_url($monitor->url, PHP_URL_HOST);

        if (! $host) {
            $result['error'] = 'No host available for SSL check';

            return $result;
        }

        if ($reason = MonitorUrlGuard::reasonForRejection('https://'.$host)) {
            $result['error'] = $reason;

            return $result;
        }

        $timeout = (int) config('laravel-crm.monitoring.request_timeout_seconds', 15);

        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => true,
                'verify_peer_name' => true,
                'SNI_enabled' => true,
                'peer_name' => $host,
            ],
        ]);

        $errno = 0;
        $errstr = '';

        $client = @stream_socket_client(
            'ssl://'.$host.':443',
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($client === false) {
            $result['error'] = $errstr !== '' ? $errstr : 'Unable to connect to '.$host.':443';

            return $result;
        }

        try {
            $params = stream_context_get_params($client);
            $cert = $params['options']['ssl']['peer_certificate'] ?? null;

            if (! $cert) {
                $result['error'] = 'No peer certificate captured';

                return $result;
            }

            $parsed = openssl_x509_parse($cert);

            if (! $parsed) {
                $result['error'] = 'Unable to parse certificate';

                return $result;
            }

            $issuerParts = $parsed['issuer'] ?? [];
            $result['issuer'] = $issuerParts['CN']
                ?? $issuerParts['O']
                ?? (is_array($issuerParts) ? implode(', ', array_map(
                    fn ($k, $v) => $k.'='.(is_array($v) ? implode('/', $v) : $v),
                    array_keys($issuerParts),
                    array_values($issuerParts)
                )) : null);

            if (isset($parsed['validTo_time_t'])) {
                $result['expires_at'] = Carbon::createFromTimestamp($parsed['validTo_time_t']);
            }

            if ($result['expires_at'] !== null && $result['expires_at']->isPast()) {
                $result['error'] = 'Certificate expired on '.$result['expires_at']->toIso8601String();
                $result['valid'] = false;

                return $result;
            }

            $result['valid'] = true;
        } catch (Throwable $e) {
            $result['error'] = $e->getMessage();
            $result['valid'] = false;
        } finally {
            if (is_resource($client)) {
                fclose($client);
            }
        }

        return $result;
    }
}
