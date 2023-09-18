<?php

namespace VentureDrake\LaravelCrm\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Schema;

class SystemCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(config('laravel-crm.update_notifications')) {
            // Since version 0.1.0
            if (! Schema::hasColumn('users', 'crm_access') || ! Schema::hasColumn('users', 'last_online_at') || ! Schema::hasColumn('users', 'current_crm_team_id')) {
                // Pre initial release version installed, you will need to add fields to users table
                // crm_access, last_online_at, current_crm_team_id
                // Delete views/vendor/laravel-crm folder or re-publish views
                flash('<strong>Important:</strong> your Laravel CRM version requires some updates to function correctly. Please see the <a href="https://github.com/venturedrake/laravel-crm" target="_blank">upgrade section</a> in the documentation.')->warning()->important();
            }

            // Since version 0.1.2
            if (! Schema::hasTable(config('laravel-crm.db_table_prefix').'settings')) {
                // settings table missing, need to publish migrations and run migrate
                // Delete views/vendor/laravel-crm folder or re-publish views
                flash('<strong>Important:</strong> your Laravel CRM version requires some updates to function correctly. Please see the <a href="https://github.com/venturedrake/laravel-crm" target="_blank">upgrade section</a> in the documentation.')->warning()->important();
            }

            // Since version 0.2.0
            if(! auth()->guest() && Schema::hasTable(config('laravel-crm.db_table_prefix').'settings') && auth()->user()->hasPermissionTo('view crm updates')) {
                if(\VentureDrake\LaravelCrm\Models\Setting::where('name', 'version')->first()->value < \VentureDrake\LaravelCrm\Models\Setting::where('name', 'version_latest')->first()->value) {
                    flash('There is a new version of Laravel CRM software available. <a href="https://github.com/venturedrake/laravel-crm" target="_blank">View version '.\VentureDrake\LaravelCrm\Models\Setting::where('name', 'version_latest')->first()->value.' details</a> or <a href="https://github.com/venturedrake/laravel-crm" target="_blank">update now</a>.')->warning()->important();
                }

                // Check if DB database required
                $dbUpdateRequired = false;

                if($setting = \VentureDrake\LaravelCrm\Models\Setting::where('name', 'db_update_0180')->first()) {
                    if($setting->value == 0) {
                        $dbUpdateRequired = true;
                    }
                }

                if($setting = \VentureDrake\LaravelCrm\Models\Setting::where('name', 'db_update_0181')->first()) {
                    if($setting->value == 0) {
                        $dbUpdateRequired = true;
                    }
                }

                if($setting = \VentureDrake\LaravelCrm\Models\Setting::where('name', 'db_update_0191')->first()) {
                    if($setting->value == 0) {
                        $dbUpdateRequired = true;
                    }
                }

                if($setting = \VentureDrake\LaravelCrm\Models\Setting::where('name', 'db_update_0193')->first()) {
                    if($setting->value == 0) {
                        $dbUpdateRequired = true;
                    }
                }

                if($setting = \VentureDrake\LaravelCrm\Models\Setting::where('name', 'db_update_0194')->first()) {
                    if($setting->value == 0) {
                        $dbUpdateRequired = true;
                    }
                }

                if($setting = \VentureDrake\LaravelCrm\Models\Setting::where('name', 'db_update_0199')->first()) {
                    if($setting->value == 0) {
                        $dbUpdateRequired = true;
                    }
                }

                if($dbUpdateRequired) {
                    flash('Your Laravel CRM software version requires some database updates to function correctly. Please <a href="https://github.com/venturedrake/laravel-crm#upgrading-from--02">update database</a>')->info()->important();
                }
            }
        }

        return $next($request);
    }
}
