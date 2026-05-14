<?php

namespace VentureDrake\LaravelCrm\Tests;

use Illuminate\Support\Facades\Cache;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Spatie\Permission\PermissionServiceProvider;
use VentureDrake\LaravelCrm\Facades\LaravelCrmFacade;
use VentureDrake\LaravelCrm\LaravelCrmServiceProvider;
use VentureDrake\LaravelCrm\Tests\Stubs\User;
use VentureDrake\LaravelCrm\View\Composers\SettingsComposer;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SettingsComposer::$cachedParameters = null;
        Cache::flush();

        // Stub the Xero facade accessor so services that call Xero::isConnected()
        // (Product/Quote/Invoice/Order services) don't blow up in tests.
        $this->app->instance('xero', new class
        {
            public function isConnected(): bool
            {
                return false;
            }

            public function __call($name, $args)
            {
                return null;
            }
        });
    }

    protected function getPackageProviders($app)
    {
        return [
            PermissionServiceProvider::class,
            LivewireServiceProvider::class,
            \BladeUI\Icons\BladeIconsServiceProvider::class,
            \BladeUI\Heroicons\BladeHeroiconsServiceProvider::class,
            \MallardDuck\BladeBoxicons\BladeBoxiconsServiceProvider::class,
            \OwenVoke\BladeFontAwesome\BladeFontAwesomeServiceProvider::class,
            \Codeat3\BladeForkAwesome\BladeForkAwesomeServiceProvider::class,
            \Mary\MaryServiceProvider::class,
            LaravelCrmServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'LaravelCrm' => LaravelCrmFacade::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('app.cipher', 'AES-256-CBC');

        $app['config']->set('laravel-crm.db_table_prefix', 'crm_');
        $app['config']->set('laravel-crm.teams', false);
        $app['config']->set('laravel-crm.encrypt_db_fields', false);
        $app['config']->set('laravel-crm.route_prefix', 'crm');
        $app['config']->set('laravel-crm.user_interface', true);
    }

    protected function defineDatabaseMigrations()
    {
        TestSchema::up();
    }

    protected function actingAsUser(array $attributes = []): User
    {
        $user = User::create(array_merge([
            'name' => 'Test User',
            'email' => 'test'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
        ], $attributes));

        $this->actingAs($user);

        return $user;
    }
}
