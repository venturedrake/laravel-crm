<?php

namespace VentureDrake\LaravelCrm\Http\Middleware;

use Closure;
use VentureDrake\LaravelCrm\Models\Role;

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
        } elseif (config('laravel-crm.teams') && auth()->user()->currentTeam && auth()->user()->currentTeam->user_id == auth()->user()->id && ! auth()->user()->hasRole('Owner')) {
            if ($role = Role::where([
                'name' => 'Owner',
                'team_id' => auth()->user()->currentTeam->id,
                'crm_role' => 1,
            ])->first()) {
                auth()->user()->assignRole($role);
            }

            auth()->user()->forceFill([
                'crm_access' => 1,
            ])->save();
        }

        return $next($request);
    }
}
