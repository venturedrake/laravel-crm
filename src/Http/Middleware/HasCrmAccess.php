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
        if (auth()->user()->crm_access != 1) {
            abort('403');
        }
        
        return $next($request);
    }
}
