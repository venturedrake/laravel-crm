<?php

namespace VentureDrake\LaravelCrm\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Support\RouteDomain;

class RouteSubdomain
{
    /**
     * Handle an incoming request.
     *
     * With route_domain set, the host must be exactly that domain. Without
     * it, the first label must be route_subdomain; a two-label host (the bare
     * domain) is redirected to the subdomain on GET and HEAD, and 404s
     * otherwise.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($domain = RouteDomain::configured()) {
            if (strtolower($request->getHost()) !== $domain) {
                abort(404);
            }

            return $next($request);
        }

        $host = explode('.', $request->getHost());

        if (count($host) == 2) {
            if ($target = RouteDomain::subdomainRedirectFor($request)) {
                return redirect()->to($target);
            }

            abort(404);
        } elseif ($host[0] != config('laravel-crm.route_subdomain')) {
            abort(404);
        }

        return $next($request);
    }
}
