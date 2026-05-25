<?php

namespace VentureDrake\LaravelCrm\Services;

use VentureDrake\LaravelCrm\Models\Monitor;
use VentureDrake\LaravelCrm\Repositories\MonitorRepository;

class MonitorService
{
    /**
     * @var MonitorRepository
     */
    private $monitorRepository;

    public function __construct(MonitorRepository $monitorRepository)
    {
        $this->monitorRepository = $monitorRepository;
    }

    public function create(array $data)
    {
        if (! empty($data['url'])) {
            $data['url'] = $this->normaliseUrl($data['url']);
            $data['host'] = parse_url($data['url'], PHP_URL_HOST);
        }

        if (! array_key_exists('user_owner_id', $data) || $data['user_owner_id'] === null) {
            $data['user_owner_id'] = auth()->user()->id ?? null;
        }

        return Monitor::create($data);
    }

    public function update(Monitor $monitor, array $data)
    {
        if (! empty($data['url'])) {
            $data['url'] = $this->normaliseUrl($data['url']);
        }

        if (isset($data['url']) && $data['url'] !== $monitor->url) {
            $data['host'] = $data['url'] ? parse_url($data['url'], PHP_URL_HOST) : null;
            $data['down_since_at'] = null;
            $data['notified_at'] = null;
            $data['ssl_notified_at'] = null;
        }

        $monitor->update($data);

        return $monitor;
    }

    private function normaliseUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return $url;
        }

        if (! preg_match('#^[a-zA-Z][a-zA-Z0-9+.\-]*://#', $url)) {
            $url = 'https://'.ltrim($url, '/');
        }

        return $url;
    }
}
