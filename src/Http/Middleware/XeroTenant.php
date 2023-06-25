<?php

namespace VentureDrake\LaravelCrm\Http\Middleware;

use Closure;
use Dcblogdev\Xero\Facades\Xero;
use Dcblogdev\Xero\Models\XeroToken;

class XeroTenant
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
