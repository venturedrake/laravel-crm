<?php

namespace VentureDrake\LaravelCrm\Http\Middleware;

use Closure;
use Dcblogdev\Xero\Facades\Xero;
use Dcblogdev\Xero\Models\XeroToken;
use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Support\XeroIntegration;

class XeroTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // The service provider already keeps this out of the stack when the suggested
        // dcblogdev/laravel-xero package is absent; repeated here because a host app
        // is free to register the middleware itself, and XeroToken/Xero below would
        // then be a fatal error rather than a skipped integration.
        if (! XeroIntegration::installed()) {
            return $next($request);
        }

        if (auth()->guest()) {
            return $next($request);
        }

        if (config('laravel-crm.teams') && auth()->hasUser() && auth()->user()->currentTeam) {
            if ($xeroToken = XeroToken::where('team_id', auth()->user()->currentTeam->id)->first()) {
                Xero::setTenantId($xeroToken->id);
            } elseif (! in_array($request->route()->getName(), [
                'laravel-crm.integrations.xero.connect',
                'app.integrations.xero.connect',
            ]) || (in_array($request->route()->getName(), [
                'laravel-crm.integrations.xero.connect',
                'app.integrations.xero.connect',
            ]) && ! request()->has('code'))) {
                Xero::setTenantId(999999999); // Workaround for issue with package
            }
        }

        return $next($request);
    }
}
