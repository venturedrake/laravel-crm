<?php

namespace VentureDrake\LaravelCrm\Http\Middleware;

use App\User;
use Carbon\Carbon;
use Closure;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Scopes\BelongsToTeamsScope;
use VentureDrake\LaravelCrm\Services\SystemCheckService;

class Settings
{
    /**
     * How long a completed seeding pass suppresses the next one.
     *
     * A day rather than forever so the pass stays genuinely self-healing — a
     * row deleted by hand comes back tomorrow without anyone clearing a cache —
     * and so the version check below still gets to run on its own three-day
     * cadence. A deploy or `php artisan cache:clear` re-arms it immediately;
     * see seedCacheKey(), which is stamped with the package version.
     */
    protected const SEED_TTL = 86400; // 24 hours

    /**
     * How long a version check (successful or not) suppresses the next attempt.
     *
     * Separate from the three-day cadence the `version` setting's updated_at
     * carries, because that row is only touched after the call returns. Without
     * this, an install whose `install_id` never landed — the API unreachable,
     * DNS slow, egress blocked — retries the blocking POST on every single
     * request.
     */
    protected const VERSION_CHECK_BACKOFF = 3600; // 1 hour

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Everything below is idempotent seeding — it only has work to do after
        // an install, an upgrade or a cache clear. Left ungated it cost 36
        // crm_settings queries plus an information_schema probe on every CRM
        // page, which on a database a network hop away is most of a second
        // before any rendering starts.
        if (Cache::get($this->seedCacheKey())) {
            return $next($request);
        }

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
                    'name' => 'organization_name',
                ], [
                    'value' => $currentTeam->name,
                ]);
            } else {
                Setting::firstOrCreate([
                    'name' => 'organization_name',
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
                'name' => 'lead_prefix',
            ], [
                'value' => 'LD-',
            ]);

            Setting::firstOrCreate([
                'name' => 'deal_prefix',
            ], [
                'value' => 'DL-',
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
                'name' => 'purchase_order_prefix',
            ], [
                'value' => 'PO-',
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

            // Seed a pending flag for every db_update the installed version has
            // reached. SystemCheckService::DB_UPDATES is the single source of
            // truth for the flag list and its version thresholds, so adding a
            // new migration only means adding it there.
            $currentVersion = app('laravel-crm.system-check')->normalisedVersion();

            foreach (SystemCheckService::DB_UPDATES as $flag => $minimumVersion) {
                if ($currentVersion < $minimumVersion) {
                    continue;
                }

                // The existence check is keyed on name alone and drops the team
                // scope, for two separate reasons.
                //
                // Name alone: the old key included global => 1, so a row written
                // by laravelcrm:install or laravelcrm:update (neither of which
                // sets global) was invisible here and got duplicated at value 0
                // — re-reporting a completed update as pending.
                //
                // No team scope: those console writes carry no team_id either,
                // because there is no authenticated user to stamp one. Under
                // `laravel-crm.teams` a scoped check cannot see them, so every
                // team would get its own copy of a flag that describes the
                // schema, and a fresh install would report updates it has
                // already applied. SystemCheckService reads these back the same
                // way. New rows still carry global, unchanged from before.
                $exists = Setting::query()
                    ->withoutGlobalScope(BelongsToTeamsScope::class)
                    ->where('name', $flag)
                    ->exists();

                if (! $exists) {
                    Setting::create([
                        'name' => $flag,
                        'global' => 1,
                        'value' => 0,
                    ]);
                }
            }

            $installIdSetting = Setting::where([
                'name' => 'install_id',
            ])->first();

            $versionCheckDue = $versionSetting
                && ! Cache::get('crm.version-check-attempted')
                && ($versionSetting->updated_at < Carbon::now()->subDays(3) || ! $installIdSetting);

            if ($versionCheckDue) {
                // Marked before the call rather than after. install_id is only
                // written from a successful response, so a failing endpoint
                // would otherwise leave the `! $installIdSetting` arm true
                // forever and put a blocking outbound POST on every page load.
                Cache::put('crm.version-check-attempted', true, self::VERSION_CHECK_BACKOFF);

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
                        // Guzzle defaults both of these to 0, meaning "wait
                        // forever". This call sits in front of every CRM page,
                        // so slow DNS or a degraded API stalls the whole
                        // request until the socket gives up.
                        'connect_timeout' => 2,
                        'timeout' => 3,
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

            Cache::put($this->seedCacheKey(), true, self::SEED_TTL);
        }

        return $next($request);
    }

    /**
     * The flag that says this install has already been seeded.
     *
     * Stamped with the package version so a deploy re-runs the pass once,
     * automatically, without anyone remembering to clear a cache.
     *
     * Partitioned by team when teams are on, because most of what is seeded
     * goes through Setting's team scope: `organization_name`, `currency`, the
     * document prefixes and so on are per-team rows. A single shared flag would
     * mean whichever team made the first request after a deploy got its rows
     * and every other team got none.
     */
    protected function seedCacheKey(): string
    {
        $key = 'crm.settings-seeded.'.(config('laravel-crm.version') ?? 'unversioned');

        if (! config('laravel-crm.teams')) {
            return $key;
        }

        return $key.'.team.'.(auth()->user()?->currentTeam?->id ?? 'none');
    }
}
