<?php

namespace VentureDrake\LaravelCrm\Tests\Feature;

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
use VentureDrake\LaravelCrm\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_facade_resolves_to_singleton(): void
    {
        $this->assertInstanceOf(LaravelCrm::class, app('laravel-crm'));
        $this->assertSame(app('laravel-crm'), app('laravel-crm'));
    }

    public function test_settings_singleton_is_registered(): void
    {
        $this->assertInstanceOf(SettingService::class, app('laravel-crm.settings'));
        $this->assertSame(app('laravel-crm.settings'), app('laravel-crm.settings'));
    }

    public function test_config_is_merged(): void
    {
        $this->assertSame('crm_', config('laravel-crm.db_table_prefix'));
        $this->assertSame('crm', config('laravel-crm.route_prefix'));
        $this->assertIsArray(config('laravel-crm.modules'));
    }

    public function test_translations_are_loaded(): void
    {
        // The package ships with English translations under "laravel-crm::lang.*"
        $value = __('laravel-crm::lang.leads');
        $this->assertNotSame('laravel-crm::lang.leads', $value);
    }

    public function test_views_are_loaded(): void
    {
        $this->assertTrue(view()->exists('laravel-crm::leads.index'));
    }

    public function test_auth_middleware_alias_is_registered(): void
    {
        $aliases = app('router')->getMiddleware();
        $this->assertArrayHasKey('auth.laravel-crm', $aliases);
    }

    public function test_console_commands_are_registered(): void
    {
        $commands = array_keys($this->app[Kernel::class]->all());

        $this->assertContains('laravelcrm:install', $commands);
        $this->assertContains('laravelcrm:add-user', $commands);
        $this->assertContains('laravelcrm:permissions', $commands);
        $this->assertContains('laravelcrm:reminders', $commands);
        $this->assertContains('laravelcrm:archive', $commands);
        $this->assertContains('laravelcrm:fields', $commands);
        $this->assertContains('laravelcrm:labels', $commands);
        $this->assertContains('laravelcrm:encrypt', $commands);
        $this->assertContains('laravelcrm:decrypt', $commands);
        $this->assertContains('laravelcrm:sample-data', $commands);
    }

    public function test_policies_are_registered_for_core_models(): void
    {
        $this->assertTrue(Gate::getPolicyFor(Lead::class) !== null);
        $this->assertTrue(Gate::getPolicyFor(Deal::class) !== null);
        $this->assertTrue(Gate::getPolicyFor(Person::class) !== null);
        $this->assertTrue(Gate::getPolicyFor(Organization::class) !== null);
        $this->assertTrue(Gate::getPolicyFor(Quote::class) !== null);
        $this->assertTrue(Gate::getPolicyFor(Order::class) !== null);
        $this->assertTrue(Gate::getPolicyFor(Invoice::class) !== null);
    }

    public function test_blade_components_are_namespaced_under_crm(): void
    {
        $aliases = app('blade.compiler')->getClassComponentAliases();
        $this->assertArrayHasKey('crm-header', $aliases);
        $this->assertArrayHasKey('crm-delete-confirm', $aliases);
        $this->assertArrayHasKey('crm-phones', $aliases);
        $this->assertArrayHasKey('crm-emails', $aliases);
        $this->assertArrayHasKey('crm-addresses', $aliases);
    }

    public function test_collection_paginate_macro_is_registered(): void
    {
        $this->assertTrue(Collection::hasMacro('paginate'));
        $collection = collect(range(1, 25));
        $paginator = $collection->paginate(10, 1);
        $this->assertCount(10, $paginator->items());
        $this->assertSame(25, $paginator->total());
    }

    public function test_named_routes_exist_for_core_resources(): void
    {
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
            $this->assertTrue(
                Route::has($name),
                "Expected named route [$name] to be registered"
            );
        }
    }

    public function test_routes_are_prefixed_with_crm_by_default(): void
    {
        $route = Route::getRoutes()->getByName('laravel-crm.leads.index');
        $this->assertNotNull($route);
        $this->assertStringStartsWith('crm/', $route->uri());
    }
}
