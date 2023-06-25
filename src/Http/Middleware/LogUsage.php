<?php

namespace VentureDrake\LaravelCrm\Http\Middleware;

use Carbon\Carbon;
use Closure;
use VentureDrake\LaravelCrm\Models\UsageRequest;

class LogUsage
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
        $response = $next($request);

        $requestTime = Carbon::createFromTimestamp($_SERVER['REQUEST_TIME']);

        UsageRequest::create([
            'host' => (method_exists($request, 'host')) ? $request->host() : $request->getHost(),
            'path' => $request->path(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'route' => $request->route()->getName(),
            'user_agent' => $request->userAgent(),
            'visitor' => crypt($request->ip(), config('hashing.encryption_key')),
            'response_time' => Carbon::now()->getTimestampMs() - $requestTime->getTimestampMs(),
            'day' => date('l', $requestTime->timestamp),
            'hour' => $requestTime->hour,
        ]);

        return $response;
    }
}
