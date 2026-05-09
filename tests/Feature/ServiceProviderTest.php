<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use VentureDrake\LaravelCrm\LaravelCrm;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Services\SettingService;

test('facade resolves to singleton', function () {
    expect(app('laravel-crm'))->toBeInstanceOf(LaravelCrm::class);
    expect(app('laravel-crm'))->toBe(app('laravel-crm'));
});

test('settings singleton is registered', function () {
    expect(app('laravel-crm.settings'))->toBeInstanceOf(SettingService::class);
    expect(app('laravel-crm.settings'))->toBe(app('laravel-crm.settings'));
});

test('config is merged', function () {
    expect(config('laravel-crm.db_table_prefix'))->toBe('crm_');
    expect(config('laravel-crm.route_prefix'))->toBe('crm');
    expect(config('laravel-crm.modules'))->toBeArray();
});

test('translations are loaded', function () {
    $value = __('laravel-crm::lang.leads');
    expect($value)->not->toBe('laravel-crm::lang.leads');
});

test('views are loaded', function () {
    expect(view()->exists('laravel-crm::leads.index'))->toBeTrue();
});

test('auth middleware alias is registered', function () {
    expect(app('router')->getMiddleware())->toHaveKey('auth.laravel-crm');
});

test('console commands are registered', function () {
    $commands = array_keys($this->app[Kernel::class]->all());

    expect($commands)
        ->toContain('laravelcrm:install')
        ->toContain('laravelcrm:add-user')
        ->toContain('laravelcrm:permissions')
        ->toContain('laravelcrm:reminders')
        ->toContain('laravelcrm:archive')
        ->toContain('laravelcrm:fields')
        ->toContain('laravelcrm:labels')
        ->toContain('laravelcrm:encrypt')
        ->toContain('laravelcrm:decrypt')
        ->toContain('laravelcrm:sample-data');
});

test('policies are registered for core models', function () {
    expect(Gate::getPolicyFor(Lead::class))->not->toBeNull();
    expect(Gate::getPolicyFor(Deal::class))->not->toBeNull();
    expect(Gate::getPolicyFor(Person::class))->not->toBeNull();
    expect(Gate::getPolicyFor(Organization::class))->not->toBeNull();
    expect(Gate::getPolicyFor(Quote::class))->not->toBeNull();
    expect(Gate::getPolicyFor(Order::class))->not->toBeNull();
    expect(Gate::getPolicyFor(Invoice::class))->not->toBeNull();
});

test('blade components are namespaced under crm', function () {
    $aliases = app('blade.compiler')->getClassComponentAliases();

    expect($aliases)
        ->toHaveKey('crm-header')
        ->toHaveKey('crm-delete-confirm')
        ->toHaveKey('crm-phones')
        ->toHaveKey('crm-emails')
        ->toHaveKey('crm-addresses');
});

test('collection paginate macro is registered', function () {
    expect(Collection::hasMacro('paginate'))->toBeTrue();

    $paginator = collect(range(1, 25))->paginate(10, 1);

    expect($paginator->items())->toHaveCount(10);
    expect($paginator->total())->toBe(25);
});

test('named routes exist for core resources', function () {
    $expected = [
        'laravel-crm.dashboard',
        'laravel-crm.leads.index',
        'laravel-crm.deals.index',
        'laravel-crm.people.index',
        'laravel-crm.organizations.index',
        'laravel-crm.quotes.index',
        'laravel-crm.orders.index',
        'laravel-crm.invoices.index',
    ];

    foreach ($expected as $name) {
        expect(Route::has($name))->toBeTrue("Expected named route [{$name}] to be registered");
    }
});

test('routes are prefixed with crm by default', function () {
    $route = Route::getRoutes()->getByName('laravel-crm.leads.index');

    expect($route)->not->toBeNull();
    expect($route->uri())->toStartWith('crm/');
});
