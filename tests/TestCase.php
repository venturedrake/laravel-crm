<?php

namespace VentureDrake\LaravelCrm\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Codeat3\BladeForkAwesome\BladeForkAwesomeServiceProvider;
use Flasher\Laravel\FlasherServiceProvider;
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

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Several CRM Livewire components import App\Models\User directly. Testbench
        // ships no such class, so alias the stub before anything tries to render them.
        if (! class_exists('App\\Models\\User')) {
            class_alias(User::class, 'App\\Models\\User');
        }

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
            // Auto-discovered in host apps. Without it the global flash() helper used by
            // the legacy src/Http/Livewire components throws FlasherContainer errors.
            FlasherServiceProvider::class,
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

        // Monitor tests target stub hostnames via Http::fake(); relax the
        // SSRF guard so DNS resolution of test-only URLs doesn't reject them.
        $app['config']->set('laravel-crm.monitoring.allow_private_targets', true);

        // Mary components ship under the `mary-` prefix in host apps; mirror that here so
        // Livewire component tests can render views that reference <x-mary-form>/<x-mary-badge>.
        $app['config']->set('mary.prefix', 'mary-');

        // cknow/laravel-money is not auto-discovered under testbench, so views that call
        // `money($amount, $currency)` can't locate the moneyphp ISO currency table. Point
        // the config at the vendor file directly so currency-formatting renders in tests.
        $app['config']->set('money.isoCurrenciesPath', __DIR__.'/../vendor/moneyphp/money/resources/currency.php');
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

    /**
     * Sign in a user scoped to an explicit permission list.
     *
     * The User stub grants everything when `crm_permissions` is null, so pass an
     * explicit list here to exercise a policy denial path. An empty array means
     * "no CRM permissions at all".
     *
     * @param  array<int, string>  $permissions
     */
    protected function actingAsUserWithPermissions(array $permissions, array $attributes = []): User
    {
        return $this->actingAsUser(array_merge([
            'crm_access' => 1,
            'crm_permissions' => json_encode($permissions),
        ], $attributes));
    }
}
