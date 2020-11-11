<?php

namespace VentureDrake\LaravelCrm;

use Illuminate\Support\ServiceProvider;
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
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'laravel-crm');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-crm');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/laravel-crm.php' => config_path('laravel-crm.php'),
            ], 'laravel-crm-config');

            // Publishing the views.
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/laravel-crm'),
            ], 'laravel-crm-views');

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/laravel-crm'),
            ], 'assets');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/laravel-crm'),
            ], 'lang');*/

            // Publishing the migrations.
            if (! class_exists('CreateLaravelCrmTables')) {
                $this->publishes([
                    __DIR__ . '/../database/migrations/create_laravel_crm_tables.php.stub' => database_path(
                        'migrations/'.date(
                            'Y_m_d_His',
                            time()
                        ).'_create_laravel_crm_tables.php'
                    ),
                ], 'laravel-crm-migrations');
            }

            if (! class_exists('LaravelCrmLeadStatusesTableSeeder')) {
                $this->publishes([
                    __DIR__ . '/../database/seeds/LaravelCrmLeadStatusesTableSeeder.php' => database_path(
                        'seeds/LaravelCrmLeadStatusesTableSeeder.php'
                    ),
                ], 'laravel-crm-seeds');
            }
            

            // Registering package commands.
            // $this->commands([]);
            
            // Register Observers
            Lead::observe(LeadObserver::class);
            Person::observe(PersonObserver::class);
            Organisation::observe(OrganisationObserver::class);
            Phone::observe(PhoneObserver::class);
            Email::observe(EmailObserver::class);
            
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/laravel-crm.php', 'laravel-crm');

        // Register the main class to use with the facade
        $this->app->singleton('laravel-crm', function () {
            return new LaravelCrm;
        });
    }
}
