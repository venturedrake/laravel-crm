<?php

namespace VentureDrake\LaravelCrm\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Schema;
use VentureDrake\LaravelCrm\Models\Setting;

class Settings
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // TBC: Check if table exists
        if (Schema::hasTable(config('laravel-crm.db_table_prefix').'settings')) {
            $settingVersion = Setting::where([
                'name' => 'version',
            ])->first();

            if (! $settingVersion) {
                $setting = Setting::create([
                    'name' => 'version',
                    'value' => config('laravel-crm.version'),
                ]);
            } else {
                $settingVersion->update([
                    'value' => config('laravel-crm.version'),
                ]);
            }

            $setting = Setting::where([
                'name' => 'app_name',
            ])->first();

            if (! $setting) {
                $setting = Setting::create([
                    'name' => 'app_name',
                    'value' => config('app.name'),
                ]);
            } else {
                $setting->update([
                    'value' => config('app.name'),
                ]);
            }

            $setting = Setting::where([
                'name' => 'app_env',
            ])->first();

            if (! $setting) {
                $setting = Setting::create([
                    'name' => 'app_env',
                    'value' => config('app.env'),
                ]);
            } else {
                $setting->update([
                    'value' => config('app.env'),
                ]);
            }

            $setting = Setting::where([
                'name' => 'app_url',
            ])->first();

            if (! $setting) {
                $setting = Setting::create([
                    'name' => 'app_url',
                    'value' => config('app.url'),
                ]);
            } else {
                $setting->update([
                    'value' => config('app.url'),
                ]);
            }
        }
        
        if ((isset($settingVersion) && $settingVersion->updated_at < Carbon::now()->subDay()) || ! isset($settingVersion)) {
            // TBC: Check server for updates, check if can connect first
        }
        
        return $next($request);
    }
}
