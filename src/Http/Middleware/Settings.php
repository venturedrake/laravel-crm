<?php

namespace VentureDrake\LaravelCrm\Http\Middleware;

use App\User;
use Carbon\Carbon;
use Closure;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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
        if (Schema::hasTable(config('laravel-crm.db_table_prefix').'settings')) {
            Setting::updateOrCreate([
                'name' => 'app_name',
            ], [
                'value' => config('app.name'),
            ]);

            Setting::updateOrCreate([
                'name' => 'app_env',
            ], [
                'value' => config('app.env'),
            ]);

            Setting::updateOrCreate([
                'name' => 'app_url',
            ], [
                'value' => config('app.url'),
            ]);

            $versionSetting = Setting::updateOrCreate([
                'name' => 'version',
            ], [
                'value' => config('laravel-crm.version'),
            ]);

            Setting::firstOrCreate([
                'name' => 'team',
            ], [
                'value' => 'related',
            ]);

            if (config('laravel-crm.teams') && ! auth()->guest() && $currentTeam = auth()->user()->currentTeam) {
                Setting::firstOrCreate([
                    'name' => 'organisation_name',
                ], [
                    'value' => $currentTeam->name,
                ]);
            } else {
                Setting::firstOrCreate([
                    'name' => 'organisation_name',
                ], [
                    'value' => config('app.name'),
                ]);
            }

            Setting::firstOrCreate([
                'name' => 'currency',
            ], [
                'value' => config('laravel-crm.currency') ?? 'USD',
            ]);

            Setting::firstOrCreate([
                'name' => 'country',
            ], [
                'value' => config('laravel-crm.country') ?? 'United States',
            ]);

            Setting::firstOrCreate([
                'name' => 'language',
            ], [
                'value' => config('laravel-crm.language') ?? 'english',
            ]);

            Setting::firstOrCreate([
                'name' => 'timezone',
            ], [
                'value' => config('laravel-crm.timezone') ?? 'UTC',
            ]);

            Setting::firstOrCreate([
                'name' => 'date_format',
            ], [
                'value' => config('laravel-crm.date_format') ?? 'Y-m-d',
            ]);

            Setting::firstOrCreate([
                'name' => 'time_format',
            ], [
                'value' => config('laravel-crm.time_format') ?? 'g:i A',
            ]);

            Setting::firstOrCreate([
                'name' => 'tax_name',
            ], [
                'value' => config('laravel-crm.tax_name') ?? 'Tax',
            ]);

            Setting::firstOrCreate([
                'name' => 'tax_rate',
            ], [
                'value' => config('laravel-crm.tax_rate') ?? 0,
            ]);

            Setting::firstOrCreate([
                'name' => 'quote_prefix',
            ], [
                'value' => 'QU-',
            ]);

            Setting::firstOrCreate([
                'name' => 'order_prefix',
            ], [
                'value' => 'ORD-',
            ]);

            Setting::firstOrCreate([
                'name' => 'invoice_prefix',
            ], [
                'value' => 'INV-',
            ]);

            Setting::firstOrCreate([
                'name' => 'delivery_prefix',
            ], [
                'value' => 'DEL-',
            ]);

            Setting::firstOrCreate([
                'name' => 'dynamic_products',
            ], [
                'value' => '1',
            ]);

            Setting::firstOrCreate([
                'name' => 'show_related_activity',
            ], [
                'value' => '0',
            ]);

            if((int) Str::replace('.', '', config('laravel-crm.version')) >= 180) {
                Setting::firstOrCreate([
                    'global' => 1,
                    'name' => 'db_update_0180',
                ], [
                    'value' => 0,
                ]);
            }

            if((int) Str::replace('.', '', config('laravel-crm.version')) >= 181) {
                Setting::firstOrCreate([
                    'global' => 1,
                    'name' => 'db_update_0181',
                ], [
                    'value' => 0,
                ]);
            }

            if((int) Str::replace('.', '', config('laravel-crm.version')) >= 191) {
                Setting::firstOrCreate([
                    'global' => 1,
                    'name' => 'db_update_0191',
                ], [
                    'value' => 0,
                ]);
            }

            if((int) Str::replace('.', '', config('laravel-crm.version')) >= 193) {
                Setting::firstOrCreate([
                    'global' => 1,
                    'name' => 'db_update_0193',
                ], [
                    'value' => 0,
                ]);
            }

            if((int) Str::replace('.', '', config('laravel-crm.version')) >= 194) {
                Setting::firstOrCreate([
                    'global' => 1,
                    'name' => 'db_update_0194',
                ], [
                    'value' => 0,
                ]);
            }

            if((int) Str::replace('.', '', config('laravel-crm.version')) >= 199) {
                Setting::firstOrCreate([
                    'global' => 1,
                    'name' => 'db_update_0199',
                ], [
                    'value' => 0,
                ]);
            }

            $installIdSetting = Setting::where([
                'name' => 'install_id',
            ])->first();

            if ($versionSetting && ($versionSetting->updated_at < Carbon::now()->subDays(3) || ! $installIdSetting)) {
                try {
                    $client = new Client();
                    $url = "https://api.laravelcrm.com/api/v1/public/version";

                    if (Schema::hasColumn('users', 'crm_access')) {
                        $userCount = User::where('crm_access', 1)->count();

                        if ($userCount == 0) {
                            $userCount = 1;
                        }
                    }

                    $response = $client->request('POST', $url, [
                        'json' => [
                            'id' => $installIdSetting->value ?? null,
                            'name' => config('app.name') ?? null,
                            'url' => config('app.url') ?? null,
                            'env' => config('app.env') ?? null,
                            'version' => config('laravel-crm.version') ?? null,
                            'server_ip' => request()->server('SERVER_ADDR') ?? null,
                            'user_ip' => request()->ip() ?? null,
                            'user_count' => $userCount ?? 1,
                        ],
                    ]);

                    $responseBody = json_decode($response->getBody());

                    if (isset($responseBody->id) && ! $installIdSetting) {
                        $installIdSetting = Setting::create([
                            'name' => 'install_id',
                            'value' => $responseBody->id,
                        ]);
                    }

                    Setting::updateOrCreate([
                        'name' => 'version_latest',
                    ], [
                        'value' => $responseBody->version,
                    ]);
                } catch (\Exception $e) {
                    //
                }

                if ($versionSetting) {
                    $versionSetting->touch();
                }
            }
        }

        return $next($request);
    }
}
