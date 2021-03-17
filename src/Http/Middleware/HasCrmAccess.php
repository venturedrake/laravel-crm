<?php

namespace VentureDrake\LaravelCrm\Http\Middleware;

use Closure;

class HasCrmAccess
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
        if (auth()->guest()) {
            return $next($request);
        }
        
        if (auth()->user()->crm_access != 1 && config('laravel-crm.crm_owner') != auth()->user()->email) {
            abort('403');
        }
        
        return $next($request);
    }
}
