<?php

namespace VentureDrake\LaravelCrm\Livewire\Monitors\Traits;

use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Services\MonitorService;
use VentureDrake\LaravelCrm\Services\MonitorUrlGuard;

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
            'type' => 'required|string|in:http,https',
            'url' => [
                'required',
                'string',
                'max:1024',
                function ($attribute, $value, $fail) {
                    if ($reason = MonitorUrlGuard::reasonForRejection($value)) {
                        $fail($reason);
                    }
                },
            ],
            'method' => 'required|string|in:GET,POST,PUT,PATCH',
            'expected_status_code' => 'required|integer|min:100|max:599',
            'interval' => 'required|integer|min:1',
            'downtime_minutes_before_alert' => 'required|integer|min:1',
            'perf_threshold_ms' => 'required|integer|min:1',
            'is_active' => 'required|boolean',
            'user_owner_id' => 'required|integer',
        ];
    }

    /**
     * The form splits the URL into a scheme selector ($type) and a hostname/path
     * input ($url). If the user pastes a full http(s) URL into the input, move its
     * scheme into the selector so the two never disagree. Any other scheme is left
     * in place so MonitorUrlGuard rejects it.
     */
    protected function splitSchemeFromUrl(): void
    {
        if (! is_string($this->url)) {
            return;
        }

        $this->url = trim($this->url);

        if (preg_match('#^(https?)://#i', $this->url, $matches)) {
            $this->type = strtolower($matches[1]);
            $this->url = substr($this->url, strlen($matches[0]));
        }
    }

    /**
     * Validate (and hand to MonitorService) the full URL built from the scheme
     * selector and the hostname input, so the SSRF guard sees the real target and
     * the stored type always matches the stored URL's scheme.
     */
    protected function prepareForValidation($attributes)
    {
        $this->splitSchemeFromUrl();

        $attributes['type'] = $this->type;
        $attributes['url'] = $this->url;

        if (is_string($this->url) && $this->url !== '' && ! preg_match('#^[a-zA-Z][a-zA-Z0-9+.\-]*://#', $this->url)) {
            $attributes['url'] = $this->type.'://'.ltrim($this->url, '/');
        }

        return $attributes;
    }

    public function typeOptions(): array
    {
        return [
            ['id' => 'https', 'name' => 'https'],
            ['id' => 'http', 'name' => 'http'],
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
