<?php

namespace VentureDrake\LaravelCrm\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class SetApiTeamContext
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->hasHeader('X-Team-ID')) {
            $this->applyCurrentTeam();

            return $next($request);
        }

        if (! $request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $teamId = $request->header('X-Team-ID');
        $user = $request->user();

        $teams = method_exists($user, 'allTeams') ? $user->allTeams() : collect();

        $team = collect($teams)->first(function ($candidate) use ($teamId) {
            return (string) ($candidate->id ?? null) === (string) $teamId;
        });

        if (! $team) {
            return response()->json([
                'message' => 'You are not a member of the requested team.',
            ], 403);
        }

        $user->setRelation('currentTeam', $team);

        if (config('laravel-crm.teams')) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
        }

        return $next($request);
    }

    protected function applyCurrentTeam(): void
    {
        if (! config('laravel-crm.teams')) {
            return;
        }

        $user = auth()->user();

        if ($user && $user->currentTeam) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($user->currentTeam->id);
        }
    }
}
