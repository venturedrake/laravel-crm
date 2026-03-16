<?php

namespace VentureDrake\LaravelCrm\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class TeamsPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (auth()->guest()) {
            return $next($request);
        }

        if (config('laravel-crm.teams') && auth()->user()->currentTeam) {
            app(PermissionRegistrar::class)->setPermissionsTeamId(auth()->user()->currentTeam->id);
        }

        return $next($request);
    }
}
