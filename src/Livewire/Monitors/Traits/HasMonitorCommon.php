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

    public string $type = 'http';

    public ?string $url = null;

    public string $method = 'GET';

    public ?int $expected_status_code = null;

    public ?string $expected_response_keyword = null;

    public int $interval = 300;

    public int $timeout = 30;

    public bool $is_active = true;

    public ?int $user_owner_id = null;

    public function boot(MonitorService $monitorService): void
    {
        $this->monitorService = $monitorService;
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:http,https,tcp',
            'url' => 'required|string|max:1024',
            'method' => 'required|string|in:GET,POST,PUT,PATCH,DELETE,HEAD',
            'expected_status_code' => 'nullable|integer|min:100|max:599',
            'expected_response_keyword' => 'nullable|string|max:255',
            'interval' => 'required|integer|min:30',
            'timeout' => 'required|integer|min:1|max:300',
            'is_active' => 'boolean',
            'user_owner_id' => 'nullable|integer',
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
            ['id' => 'DELETE', 'name' => 'DELETE'],
            ['id' => 'HEAD', 'name' => 'HEAD'],
        ];
    }
}
