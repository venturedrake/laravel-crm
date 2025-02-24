<?php

namespace VentureDrake\LaravelCrm\View\Composers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use VentureDrake\LaravelCrm\Models\Setting;

class SettingsComposer
{
    public static ?array $cachedParameters = null;

    public function compose(View $view)
    {
        static::$cachedParameters ??= Cache::remember(
            self::class,
            now()->addHour(),
            function () {
                $defaults = [
                    'dateFormat' => 'Y/m/d',
                    'timeFormat' => 'H:i',
                    'timezone' => 'UTC',
                    'taxName' => 'Tax',
                    'dynamicProducts' => 'true',
                ];

                if (! Schema::hasTable(config('laravel-crm.db_table_prefix').'settings')) {
                    return $defaults;
                }

                if ($dynamicProductsSetting = Setting::where('name', 'dynamic_products')->first()) {
                    if ($dynamicProductsSetting->value == 1) {
                        $dynamicProducts = 'true';
                    } else {
                        $dynamicProducts = 'false';
                    }
                } else {
                    $dynamicProducts = $defaults['dynamicProducts'];
                }

                return [
                    'dateFormat' => Setting::where('name', 'date_format')->first()?->value ?? $defaults['dateFormat'],
                    'timeFormat' => Setting::where('name', 'time_format')->first()?->value ?? $defaults['timeFormat'],
                    'timezone' => Setting::where('name', 'timezone')->first()?->value ?? $defaults['timezone'],
                    'taxName' => Setting::where('name', 'tax_name')->first()?->value ?? $defaults['taxName'],
                    'dynamicProducts' => $dynamicProducts,
                ];
            }
        );

        $view->with(static::$cachedParameters);
    }
}
