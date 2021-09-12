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
        
        if (! config('laravel-crm.teams') && config('laravel-crm.crm_owner') == auth()->user()->email && (! auth()->user()->hasRole('Owner') || ! auth()->user()->hasCrmAccess())) {
            auth()->user()->assignRole('Owner');

            auth()->user()->forceFill([
                'crm_access' => 1,
            ])->save();
        } elseif (config('laravel-crm.teams') && auth()->user()->currentTeam->user_id == auth()->user()->id && ! auth()->user()->hasRole('Owner')) {
            auth()->user()->assignRole('Owner');

            auth()->user()->forceFill([
                'crm_access' => 1,
            ])->save();
        }
        
        return $next($request);
    }
}
