<?php

namespace VentureDrake\LaravelCrm\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;
use VentureDrake\LaravelCrm\Models\UsageRequest;

class LogUsage
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        try {
            $requestTime = Carbon::createFromTimestamp($_SERVER['REQUEST_TIME']);

            UsageRequest::create([
                'host' => (method_exists($request, 'host')) ? $request->host() : $request->getHost(),
                'path' => $request->path(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'route' => optional($request->route())->getName(),
                'user_agent' => $request->userAgent(),
                'visitor' => crypt($request->ip(), config('hashing.encryption_key')),
                'response_time' => Carbon::now()->getTimestampMs() - $requestTime->getTimestampMs(),
                'day' => date('l', $requestTime->timestamp),
                'hour' => $requestTime->hour,
            ]);
        } catch (Throwable $e) {
            // Never let usage logging break the request (e.g. table missing before
            // migrations have run, transient DB errors, etc.).
            Log::warning('LaravelCrm LogUsage middleware failed: '.$e->getMessage());
        }

        return $response;
    }
}
