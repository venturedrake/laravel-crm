<?php

namespace VentureDrake\LaravelCrm\Livewire\Monitors\Traits;

use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Services\MonitorService;

trait HasMonitorCommon
{
    use Toast;

    protected MonitorService $monitorService;

    public ?string $name = null;

    public ?string $description = null;

    public string $type = 'https';

    public ?string $url = null;

    public string $method = 'GET';

    public ?int $expected_status_code = 200;

    public int $interval = 5;

    public int $downtime_minutes_before_alert = 5;

    public int $perf_threshold_ms = 3500;

    public bool $is_active = true;

    public ?int $user_owner_id = null;

    public function boot(MonitorService $monitorService): void
    {
        $this->monitorService = $monitorService;
    }

    protected function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:http,https,tcp',
            'url' => ['required', 'string', 'max:1024', new \VentureDrake\LaravelCrm\Rules\PublicHttpUrl],
            'method' => 'required|string|in:GET,POST,PUT,PATCH',
            'expected_status_code' => 'required|integer|min:100|max:599',
            'interval' => 'required|integer|min:1',
            'downtime_minutes_before_alert' => 'required|integer|min:1',
            'perf_threshold_ms' => 'required|integer|min:1',
            'is_active' => 'required|boolean',
            'user_owner_id' => 'required|integer',
        ];
    }

    public function typeOptions(): array
    {
        return [
            ['id' => 'http', 'name' => 'HTTP'],
            ['id' => 'https', 'name' => 'HTTPS'],
            ['id' => 'tcp', 'name' => 'TCP'],
        ];
    }

    public function methodOptions(): array
    {
        return [
            ['id' => 'GET', 'name' => 'GET'],
            ['id' => 'POST', 'name' => 'POST'],
            ['id' => 'PUT', 'name' => 'PUT'],
            ['id' => 'PATCH', 'name' => 'PATCH'],
        ];
    }
}
