<?php

namespace VentureDrake\LaravelCrm\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LastOnlineAt
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

        $user = auth()->user();

        // Throttle: only update once every 5 minutes to avoid a write on every request
        // (5-minute resolution also enables accurate "online" presence for chat widgets)
        if (! $user->last_online_at || Carbon::parse($user->last_online_at)->diffInMinutes(now()) >= 5) {
            DB::table('users')
                ->where('id', $user->id)
                ->update(['last_online_at' => now()]);
        }

        return $next($request);
    }
}
