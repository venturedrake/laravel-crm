<?php

namespace VentureDrake\LaravelCrm\Http\Middleware;

use Closure;

class RouteSubdomain
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
        $host = explode(".", request()->getHost());

        if(count($host) == 2) {
            abort(404);
        } elseif($host[0] != config('laravel-crm.route_subdomain')) {
            abort(404);
        }

        return $next($request);
    }
}
