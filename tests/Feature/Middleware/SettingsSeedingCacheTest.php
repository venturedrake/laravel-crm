<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use VentureDrake\LaravelCrm\Http\Middleware\Settings as SettingsMiddleware;
use VentureDrake\LaravelCrm\Models\Setting;

/*
 * The Settings middleware seeds every default the CRM needs — four
 * updateOrCreate, nineteen firstOrCreate, eight db_update existence checks and
 * an install_id lookup. It sits in front of every CRM page, so left ungated
 * that was 36 crm_settings queries plus an information_schema probe on every
 * single request. The work is idempotent and only has anything to do after an
 * install, an upgrade or a cache clear, so a version-stamped cache flag skips
 * it on a warm request.
 */

beforeEach(function () {
    config(['laravel-crm.version' => '2.4.2']);

    // Without it the phone-home block would attempt a real HTTP call.
    Setting::firstOrCreate(['name' => 'install_id'], ['value' => 'test-install']);

    Cache::flush();
});

/**
 * Run the middleware the way a request would and return the queries it issued.
 *
 * @return array<int, string>
 */
function settingsMiddlewareQueries(): array
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    (new SettingsMiddleware)->handle(Request::create('/'), fn ($request) => $request);

    $queries = array_map(fn ($query) => $query['query'], DB::getQueryLog());

    DB::disableQueryLog();

    return $queries;
}

it('seeds on a cold cache', function () {
    expect(settingsMiddlewareQueries())->not->toBeEmpty();

    expect(Setting::where('name', 'currency')->exists())->toBeTrue()
        ->and(Setting::where('name', 'quote_prefix')->exists())->toBeTrue();
});

it('issues no queries at all on a warm cache', function () {
    settingsMiddlewareQueries();

    expect(settingsMiddlewareQueries())->toBe([]);
});

it('passes the request along on a warm cache', function () {
    settingsMiddlewareQueries();

    $request = Request::create('/');
    $handled = (new SettingsMiddleware)->handle($request, fn ($passed) => $passed);

    expect($handled)->toBe($request);
});

it('re-seeds after the package version changes', function () {
    settingsMiddlewareQueries();

    expect(settingsMiddlewareQueries())->toBe([]);

    // What a deploy looks like: same cache, newer code.
    config(['laravel-crm.version' => '2.5.0']);

    expect(settingsMiddlewareQueries())->not->toBeEmpty();
});

it('re-seeds after a cache clear, so a deleted row heals itself', function () {
    settingsMiddlewareQueries();

    Setting::where('name', 'currency')->delete();
    Cache::flush();

    settingsMiddlewareQueries();

    expect(Setting::where('name', 'currency')->exists())->toBeTrue();
});

it('does not mark itself seeded when the settings table is missing', function () {
    // Point the middleware at a prefix nothing has migrated — the shape of an
    // install that has not run `php artisan migrate` yet. It must keep probing
    // rather than latch a flag that says "seeded" for a day.
    config(['laravel-crm.db_table_prefix' => 'not_migrated_']);

    settingsMiddlewareQueries();

    expect(Cache::get('crm.settings-seeded.2.4.2'))->toBeNull();
});

it('keys the flag per team so a second team still gets its own rows', function () {
    config(['laravel-crm.teams' => true]);

    $this->actingAsUserWithPermissions([], ['current_team_id' => 1]);
    settingsMiddlewareQueries();

    expect(settingsMiddlewareQueries())->toBe([]);

    // A different team's first request must not be waved through on the flag
    // team one set: currency, the document prefixes and organization_name are
    // all team-scoped rows.
    $this->actingAsUserWithPermissions([], ['current_team_id' => 2]);

    expect(settingsMiddlewareQueries())->not->toBeEmpty();
});
