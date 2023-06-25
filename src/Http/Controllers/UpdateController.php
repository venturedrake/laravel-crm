<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use App\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use VentureDrake\LaravelCrm\Models\Setting;

class UpdateController extends Controller
{
    /**
     * Display update information
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if ($installIdSetting = Setting::where(['name' => 'install_id',])->first()) {
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

                Setting::updateOrCreate([
                    'name' => 'version_latest',
                ], [
                    'value' => $responseBody->version,
                ]);
            } catch (\Exception $e) {
                //
            }
        }

        return view('laravel-crm::updates.index');
    }

    /**
     * Update
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        //
    }
}
