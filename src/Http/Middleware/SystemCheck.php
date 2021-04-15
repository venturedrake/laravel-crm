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
        // Roles are missing, redirect to must update page
        // Run db seeder to add roles/permissions
        // Delete views/vendor/laravel-crm folder or re-publish views

       
        return $next($request);
    }
}
