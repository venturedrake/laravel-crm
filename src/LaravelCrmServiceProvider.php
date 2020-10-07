<?php

namespace VentureDrake\LaravelCrm;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

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
                __DIR__ . '/../config/config.php' => config_path('laravel-crm.php'),
            ], 'config');

            // Publishing the views.
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/laravel-crm'),
            ], 'views');

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/laravel-crm'),
            ], 'assets');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/laravel-crm'),
            ], 'lang');*/

            // Publishing the migrations.
            if (!class_exists('CreateLaravelCrmTables')) {
                $this->publishes([
                    __DIR__ . '/../database/migrations/create_laravel_crm_tables.php.stub' => database_path(
                        'migrations/'.date(
                        'y_m_d_His',
                        time().'_create_laravel_crm_tables.php'
                    )
                    ),
                ], 'migrations');
            }
            

            // Registering package commands.
            // $this->commands([]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'laravel-crm');

        // Register the main class to use with the facade
        $this->app->singleton('laravel-crm', function () {
            return new LaravelCrm;
        });
    }
}
