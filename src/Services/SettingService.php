<?php

namespace VentureDrake\LaravelCrm\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use VentureDrake\LaravelCrm\Models\Setting;

class SettingService
{
    protected string $cacheKey = 'app.crm-settings';

    protected int $ttl = 3600; // 1 hour (adjust)

    public function all(): array
    {
        return Cache::remember($this->cacheKey, $this->ttl, function () {
            return Setting::pluck('value', 'name')
                ->toArray();
        });
    }

    public function get(string $name, $default = null)
    {
        return Arr::get($this->all(), $name, $default);
    }

    public function set($name, $value, $label = null)
    {
        return Setting::updateOrCreate([
            'name' => $name,
        ], [
            'value' => $value,
            'label' => $label,
        ]);
    }

    public function forgetCache(): void
    {
        Cache::forget($this->cacheKey);
    }
}
