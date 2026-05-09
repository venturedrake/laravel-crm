<?php

use Illuminate\Support\Facades\Cache;
use VentureDrake\LaravelCrm\Models\Setting;

test('set creates a new setting', function () {
    $setting = app('laravel-crm.settings')->set('lead_prefix', 'L', 'Lead Prefix');

    expect($setting)->toBeInstanceOf(Setting::class);
    $this->assertDatabaseHas('crm_settings', [
        'name' => 'lead_prefix',
        'value' => 'L',
        'label' => 'Lead Prefix',
    ]);
});

test('set updates an existing setting', function () {
    $service = app('laravel-crm.settings');
    $service->set('currency', 'USD');
    $service->set('currency', 'AUD');

    expect(Setting::where('name', 'currency')->count())->toBe(1);
    expect(Setting::where('name', 'currency')->first()->value)->toBe('AUD');
});

test('get returns default when setting missing', function () {
    $service = app('laravel-crm.settings');

    expect($service->get('does_not_exist', 'fallback'))->toBe('fallback');
    expect($service->get('does_not_exist'))->toBeNull();
});

test('all returns settings keyed by name', function () {
    $service = app('laravel-crm.settings');
    $service->set('a', '1');
    $service->set('b', '2');
    $service->forgetCache();

    $all = $service->all();

    expect($all['a'])->toBe('1');
    expect($all['b'])->toBe('2');
});

test('all is cached', function () {
    $service = app('laravel-crm.settings');
    $service->set('cached', 'first');
    $service->forgetCache();

    expect($service->get('cached'))->toBe('first');

    Setting::where('name', 'cached')->update(['value' => 'second']);

    // Cached value still returned
    expect($service->get('cached'))->toBe('first');

    $service->forgetCache();

    expect($service->get('cached'))->toBe('second');
});

test('first returns underlying model', function () {
    $service = app('laravel-crm.settings');
    $service->set('lookup', 'value');

    $found = $service->first('lookup');

    expect($found)->toBeInstanceOf(Setting::class);
    expect($found->value)->toBe('value');
});

test('forget cache removes cached entry', function () {
    $service = app('laravel-crm.settings');
    $service->set('x', 'y');
    $service->all();

    expect(Cache::has('app.crm-settings'))->toBeTrue();

    $service->forgetCache();

    expect(Cache::has('app.crm-settings'))->toBeFalse();
});
