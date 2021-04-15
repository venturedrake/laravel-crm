<?php

namespace VentureDrake\LaravelCrm\Http\Middleware;

use App\User;
use Carbon\Carbon;
use Closure;
use GuzzleHttp\Client;
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

            $installIdSetting = Setting::where([
                'name' => 'install_id',
            ])->first();
            
            if ($versionSetting && ($versionSetting->updated_at < Carbon::now()->subDays(3) || ! $installIdSetting)) {
                try {
                    $client = new Client();
                    $url = "https://beta.laravelcrm.com/api/public/version";

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
