<?php

namespace VentureDrake\LaravelCrm\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Codeat3\BladeForkAwesome\BladeForkAwesomeServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\SanctumServiceProvider;
use Livewire\LivewireServiceProvider;
use MallardDuck\BladeBoxicons\BladeBoxiconsServiceProvider;
use Mary\MaryServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use OwenVoke\BladeFontAwesome\BladeFontAwesomeServiceProvider;
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
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeBoxiconsServiceProvider::class,
            BladeFontAwesomeServiceProvider::class,
            BladeForkAwesomeServiceProvider::class,
            MaryServiceProvider::class,
            SanctumServiceProvider::class,
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

        // Mary components ship under the `mary-` prefix in host apps; mirror that here so
        // Livewire component tests can render views that reference <x-mary-form>/<x-mary-badge>.
        $app['config']->set('mary.prefix', 'mary-');
    }

    protected function defineDatabaseMigrations()
    {
        TestSchema::up();

        if (! Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
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
