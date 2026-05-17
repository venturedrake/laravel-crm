<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use App\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use VentureDrake\LaravelCrm\Models\Setting;

class UpdateController extends Controller
{
    /**
     * Display update information
     *
     * @return Response
     */
    public function index()
    {
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
                $client = new Client;
                $url = 'https://api.laravelcrm.com/api/v2/public/version';

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

        return view('laravel-crm::updates.index');
    }

    /**
     * Update
     *
     * @return Response
     */
    public function update(Request $request)
    {
        //
    }
}
