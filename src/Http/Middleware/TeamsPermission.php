<?php

namespace VentureDrake\LaravelCrm\Http\Middleware;

use Closure;

class TeamsPermission
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

        if (config('laravel-crm.teams') && auth()->user()->currentTeam) {
            app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(auth()->user()->currentTeam->id);
        }

        return $next($request);
    }
}
