<?php

namespace VentureDrake\LaravelCrm\Http\Middleware;

use Closure;

class Settings
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
        // config('laravel-crm.version')
        
        // Load into settings the package version
        
        // APP_NAME, APP_ENV, APP_URL
        
        // Check server for updates
        
        return $next($request);
    }
}
