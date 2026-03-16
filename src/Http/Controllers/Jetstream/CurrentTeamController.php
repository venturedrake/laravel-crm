<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Jetstream;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Jetstream\Jetstream;
use Spatie\Permission\PermissionRegistrar;

class CurrentTeamController extends Controller
{
    /**
     * Update the authenticated user's current team.
     *
     * @return RedirectResponse
     */
    public function update(Request $request)
    {
        $team = Jetstream::newTeamModel()->findOrFail($request->team_id);

        if ($request->user()->switchTeam($team)) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        } else {
            abort(403);
        }

        return redirect(config('fortify.home'), 303);
    }
}
