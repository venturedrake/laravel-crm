<?php

use Illuminate\Support\Facades\Cache;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Observers\SettingObserver;
use VentureDrake\LaravelCrm\Services\SettingService;
use VentureDrake\LaravelCrm\Services\SystemCheckService;

/**
 * Warm both caches and assert they really are warm, so a later "cache was
 * busted" assertion cannot pass simply because the cache was never populated.
 */
function warmSettingCaches(): void
{
    app('laravel-crm.settings')->all();
    app('laravel-crm.system-check')->alerts();

    expect(Cache::has(app('laravel-crm.settings')->cacheKey()))->toBeTrue('settings cache should be warm');
    expect(Cache::has(SystemCheckService::CACHE_KEY))->toBeTrue('system-check cache should be warm');
}

function settingCachesAreCold(): bool
{
    return ! Cache::has(app('laravel-crm.settings')->cacheKey())
        && ! Cache::has(SystemCheckService::CACHE_KEY);
}

beforeEach(function () {
    app('laravel-crm.settings')->forgetCache();
    Cache::forget(SystemCheckService::CACHE_KEY);
    config(['laravel-crm.update_notifications' => true]);
});

test('SettingService::class resolves the laravel-crm.settings singleton', function () {
    expect(app(SettingService::class))->toBeInstanceOf(SettingService::class)
        ->and(app(SettingService::class))->toBe(app('laravel-crm.settings'))
        ->and(app(SettingService::class))->toBe(app(SettingService::class));
});

test('SystemCheckService::class and laravel-crm.system-check resolve the same singleton', function () {
    expect(app('laravel-crm.system-check'))->toBeInstanceOf(SystemCheckService::class)
        ->and(app(SystemCheckService::class))->toBe(app('laravel-crm.system-check'))
        ->and(app(SystemCheckService::class))->toBe(app(SystemCheckService::class));
});

test('the system-check singleton is built from the shared settings singleton', function () {
    $property = new ReflectionProperty(SystemCheckService::class, 'settingService');
    $property->setAccessible(true);

    expect($property->getValue(app(SystemCheckService::class)))->toBe(app('laravel-crm.settings'));
});

test('creating a setting forgets both caches', function () {
    warmSettingCaches();

    Setting::create(['name' => 'observer_created_probe', 'value' => 'a']);

    expect(settingCachesAreCold())->toBeTrue();
});

test('updating a setting to a new value forgets both caches', function () {
    $setting = Setting::create(['name' => 'observer_updated_probe', 'value' => 'a']);

    warmSettingCaches();

    $setting->update(['value' => 'b']);

    expect(settingCachesAreCold())->toBeTrue();
});

test('deleting a setting forgets both caches', function () {
    $setting = Setting::create(['name' => 'observer_deleted_probe', 'value' => 'a']);

    warmSettingCaches();

    $setting->delete();

    expect(settingCachesAreCold())->toBeTrue();
});

/*
 * Setting has no SoftDeletes, so restored/forceDeleted can never fire from a
 * model call. Invoke the handlers directly — the contract under test is that
 * all five methods bust both caches, not that Eloquent dispatches them here.
 */
test('the restored handler forgets both caches', function () {
    warmSettingCaches();

    (new SettingObserver)->restored(new Setting(['name' => 'x', 'value' => 'y']));

    expect(settingCachesAreCold())->toBeTrue();
});

test('the forceDeleted handler forgets both caches', function () {
    warmSettingCaches();

    (new SettingObserver)->forceDeleted(new Setting(['name' => 'x', 'value' => 'y']));

    expect(settingCachesAreCold())->toBeTrue();
});

test('a changed updateOrCreate write busts the cache but a clean one does not', function () {
    Setting::updateOrCreate(['name' => 'observer_upsert_probe'], ['value' => 'a']);

    warmSettingCaches();

    // Same value: Eloquent finds zero dirty attributes, so it issues no UPDATE
    // and fires no `updated` event. This is what keeps the per-request
    // app_name/app_env/app_url/version upserts in the Settings middleware from
    // busting the system-check cache on every single request.
    Setting::updateOrCreate(['name' => 'observer_upsert_probe'], ['value' => 'a']);

    expect(Cache::has(SystemCheckService::CACHE_KEY))->toBeTrue('a clean upsert must not bust the cache');

    Setting::updateOrCreate(['name' => 'observer_upsert_probe'], ['value' => 'b']);

    expect(settingCachesAreCold())->toBeTrue();
});

test('the busted system-check cache is recomputed with the new setting value', function () {
    app('laravel-crm.settings')->set('version', '2.0.0');
    app('laravel-crm.settings')->set('version_latest', '2.0.0');
    app('laravel-crm.settings')->forgetCache();

    expect(array_column(app('laravel-crm.system-check')->alerts(), 'type'))
        ->not->toContain(SystemCheckService::UPDATE_AVAILABLE);

    // Mirrors UpdateController::index writing a changed version_latest.
    app('laravel-crm.settings')->set('version_latest', '2.10.0');

    expect(array_column(app('laravel-crm.system-check')->alerts(), 'type'))
        ->toContain(SystemCheckService::UPDATE_AVAILABLE);
});
