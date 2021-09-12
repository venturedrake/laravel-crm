<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Jetstream;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Jetstream\Jetstream;

class CurrentTeamController extends Controller
{
    /**
     * Update the authenticated user's current team.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $team = Jetstream::newTeamModel()->findOrFail($request->team_id);

        if ($request->user()->switchTeam($team)) {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        } else {
            abort(403);
        }

        return redirect(config('fortify.home'), 303);
    }
}
