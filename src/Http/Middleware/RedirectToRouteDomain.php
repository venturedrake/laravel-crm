<?php

namespace VentureDrake\LaravelCrm\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Support\RouteDomain;

/**
 * Sends the bare domain and www. to config('laravel-crm.route_domain').
 *
 * Registered as global HTTP middleware rather than on a route group: the CRM
 * routes are bound to route_domain, so a request for any other host matches
 * no CRM route, and route-group middleware never runs on an unmatched route.
 */
class RedirectToRouteDomain
{
    public function handle(Request $request, Closure $next)
    {
        if ($target = RouteDomain::redirectFor($request)) {
            return redirect()->to($target);
        }

        return $next($request);
    }
}
