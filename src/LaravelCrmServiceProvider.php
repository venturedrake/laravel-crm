<?php

namespace VentureDrake\LaravelCrm;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use VentureDrake\LaravelCrm\Console\LaravelCrmInstall;
use VentureDrake\LaravelCrm\Http\Middleware\Authenticate;
use VentureDrake\LaravelCrm\Http\Middleware\HasCrmAccess;
use VentureDrake\LaravelCrm\Http\Middleware\LastOnlineAt;
use VentureDrake\LaravelCrm\Http\Middleware\Settings;
use VentureDrake\LaravelCrm\Http\Middleware\SystemCheck;
use VentureDrake\LaravelCrm\Models\Email;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Organisation;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Phone;
use VentureDrake\LaravelCrm\Observers\EmailObserver;
use VentureDrake\LaravelCrm\Observers\LeadObserver;
use VentureDrake\LaravelCrm\Observers\OrganisationObserver;
use VentureDrake\LaravelCrm\Observers\PersonObserver;
use VentureDrake\LaravelCrm\Observers\PhoneObserver;

class LaravelCrmServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'VentureDrake\LaravelCrm\Models\Lead' => \VentureDrake\LaravelCrm\Policies\LeadPolicy::class,
        'VentureDrake\LaravelCrm\Models\Deal' => \VentureDrake\LaravelCrm\Policies\DealPolicy::class,
        'VentureDrake\LaravelCrm\Models\Person' => \VentureDrake\LaravelCrm\Policies\PersonPolicy::class,
        'VentureDrake\LaravelCrm\Models\Organisation' => \VentureDrake\LaravelCrm\Policies\OrganisationPolicy::class,
    ];
    
    /**
     * Bootstrap the application services.
     */
    public function boot(Router $router, Filesystem $filesystem)
    {
        $this->registerPolicies();
        
        /*
         * Optional methods to load your package assets
         */
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'laravel-crm');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-crm');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Middleware
        $router->aliasMiddleware('auth.laravel-crm', Authenticate::class);
        $router->pushMiddlewareToGroup('crm', Settings::class);
        $router->pushMiddlewareToGroup('api', Settings::class);
        $router->pushMiddlewareToGroup('crm', HasCrmAccess::class);
        $router->pushMiddlewareToGroup('api', HasCrmAccess::class);
        $router->pushMiddlewareToGroup('crm', LastOnlineAt::class);
        $router->pushMiddlewareToGroup('crm', SystemCheck::class);
        
        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/laravel-crm.php' => config_path('laravel-crm.php'),
            ], 'config');

            // Publishing the views.
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/laravel-crm'),
            ], 'views');

            // Publishing assets.
            $this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/laravel-crm'),
            ], 'assets');

            // Publishing the translation files.
            $this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/laravel-crm'),
            ], 'lang');

            // Publishing the migrations.
            $this->publishes([
                __DIR__ . '/../database/migrations/create_laravel_crm_tables.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_tables.php'),
                __DIR__ . '/../database/migrations/create_laravel_crm_settings_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_settings_table.php'),
                __DIR__ . '/../database/migrations/add_fields_to_roles_permissions_tables.php.stub' => $this->getMigrationFileName($filesystem, 'add_fields_to_roles_permissions_tables.php'),
                __DIR__ . '/../database/migrations/add_label_editable_fields_to_laravel_crm_settings_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_label_editable_fields_to_laravel_crm_settings_table.php'),
            ], 'migrations');

            // Publishing the seeders
            if (! class_exists('LaravelCrmTablesSeeder')) {
                if (app()->version() >= 8) {
                    $this->publishes([
                        __DIR__ . '/../database/seeders/LaravelCrmTablesSeeder.php' => database_path(
                            'seeders/LaravelCrmTablesSeeder.php'
                        ),
                    ], 'seeders');
                } else {
                    $this->publishes([
                        __DIR__ . '/../database/seeders/LaravelCrmTablesSeeder.php' => database_path(
                            'seeds/LaravelCrmTablesSeeder.php'
                        ),
                    ], 'seeders');
                }
            }

            // Registering package commands.
            if ($this->app->runningInConsole()) {
                $this->commands([
                    LaravelCrmInstall::class,
                ]);
            }
            
            // Register Observers
            Lead::observe(LeadObserver::class);
            Person::observe(PersonObserver::class);
            Organisation::observe(OrganisationObserver::class);
            Phone::observe(PhoneObserver::class);
            Email::observe(EmailObserver::class);

            // Register the model factories
            /* $this->app->make('Illuminate\Database\Eloquent\Factory')
                ->load(__DIR__.'/../database/factories'); */
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/package.php', 'laravel-crm');
        $this->mergeConfigFrom(__DIR__ . '/../config/laravel-crm.php', 'laravel-crm');

        // Register the main class to use with the facade
        $this->app->singleton('laravel-crm', function () {
            return new LaravelCrm;
        });
    }
    
    protected function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');
        });
    }
    
    protected function routeConfiguration()
    {
        return [
            'prefix' => config('laravel-crm.route_prefix'),
            'middleware' => array_unique(array_merge(['web','crm'], config('laravel-crm.route_middleware') ?? [])),
        ];
    }

    /**
     * Returns existing migration file if found, else uses the current timestamp.
     *
     * @param Filesystem $filesystem
     * @return string
     */
    protected function getMigrationFileName(Filesystem $filesystem, $filename): string
    {
        $timestamp = date('Y_m_d_His');

        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem, $filename) {
                return $filesystem->glob($path.'*_'.$filename);
            })->push($this->app->databasePath()."/migrations/{$timestamp}_".$filename)
            ->first();
    }

    /**
     * Register the application's policies.
     *
     * @return void
     */
    public function registerPolicies()
    {
        foreach ($this->policies() as $key => $value) {
            Gate::policy($key, $value);
        }
    }

    /**
     * Get the policies defined on the provider.
     *
     * @return array
     */
    public function policies()
    {
        return $this->policies;
    }
}
