<?php

namespace VentureDrake\LaravelCrm\Support;

use Illuminate\Http\Request;

/**
 * Works out which host the CRM's routes are bound to.
 *
 * config('laravel-crm.route_domain') names it outright. Without it,
 * route_subdomain is prefixed onto the domain of the request that booted the
 * app, which is what every release before 2.6.0 did. That guess depends on
 * the request, so it differs under route:cache and in queue workers.
 */
class RouteDomain
{
    public static function configured(): ?string
    {
        $domain = config('laravel-crm.route_domain');

        if (! is_string($domain) || trim($domain) === '') {
            return null;
        }

        return strtolower(trim($domain));
    }

    /**
     * The domain to bind the main and auth route groups to, or null for none.
     */
    public static function forRoutes(?Request $request = null): ?string
    {
        if ($domain = static::configured()) {
            return $domain;
        }

        if (! config('laravel-crm.route_subdomain')) {
            return null;
        }

        $host = explode('.', ($request ?? request())->getHost());

        if (count($host) == 3) { // .com
            return config('laravel-crm.route_subdomain').'.'.$host[(count($host) - 2)].'.'.end($host);
        }

        if (count($host) == 4) { // .com.au
            return config('laravel-crm.route_subdomain').'.'.$host[(count($host) - 3)].'.'.$host[(count($host) - 2)].'.'.end($host);
        }

        return null;
    }

    /**
     * The other hosts of the same site, which redirect to route_domain: the
     * bare domain and its www. A route_domain of two labels has neither.
     *
     * @return list<string>
     */
    public static function siblingHosts(): array
    {
        $domain = static::configured();

        if (! $domain || substr_count($domain, '.') < 2) {
            return [];
        }

        $bare = substr($domain, strpos($domain, '.') + 1);

        return array_values(array_diff([$bare, 'www.'.$bare], [$domain]));
    }

    /**
     * Where to send a request for a sibling host, or null to let it through.
     *
     * Only GET and HEAD, only when the CRM owns the whole path space
     * (route_prefix empty), and never for the API or the public /p links:
     * those are bound to no domain on purpose, and a signed portal URL stops
     * validating once its host changes.
     */
    public static function redirectFor(Request $request): ?string
    {
        $domain = static::configured();

        if (! $domain
            || ! in_array($request->getMethod(), ['GET', 'HEAD'], true)
            || config('laravel-crm.route_prefix')
            || ! in_array(strtolower($request->getHost()), static::siblingHosts(), true)
            || $request->is('api', 'api/*', 'p', 'p/*')) {
            return null;
        }

        $port = in_array($request->getPort(), [80, 443, null], true) ? '' : ':'.$request->getPort();

        return $request->getScheme().'://'.$domain.$port.$request->getRequestUri();
    }

    /**
     * Whether a two-label host should be sent to route_subdomain (no
     * route_domain set). Returns the URL, or null.
     */
    public static function subdomainRedirectFor(Request $request): ?string
    {
        if (static::configured()
            || ! config('laravel-crm.route_subdomain')
            || ! in_array($request->getMethod(), ['GET', 'HEAD'], true)
            || count(explode('.', $request->getHost())) != 2) {
            return null;
        }

        return $request->getScheme().'://'.config('laravel-crm.route_subdomain').'.'.$request->getHttpHost().$request->getRequestUri();
    }
}
