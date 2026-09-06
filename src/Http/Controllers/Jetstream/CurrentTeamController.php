<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Jetstream;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Laravel\Jetstream\Jetstream;
use Spatie\Permission\PermissionRegistrar;
use VentureDrake\LaravelCrm\Models\Team as CrmTeam;
use VentureDrake\LaravelCrm\Scopes\BelongsToTeamsScope;

class CurrentTeamController extends Controller
{
    /**
     * Update the authenticated user's current team.
     *
     * @return RedirectResponse
     */
    public function update(Request $request)
    {
        $user = $request->user();
        $team = $this->resolveTeamModel()->findOrFail($request->team_id);

        if (method_exists($user, 'switchTeam')) {
            if (! $user->switchTeam($team)) {
                abort(403);
            }
        } elseif (Schema::hasColumn($user->getTable(), 'current_team_id')) {
            // switchTeam() checks membership itself; this branch has to. Without
            // it any authenticated user could PUT any team_id and be switched
            // into that tenant's data.
            if (! $this->isMemberOf($user, $team)) {
                abort(403);
            }

            $user->forceFill(['current_team_id' => $team->id])->save();
        } else {
            abort(403);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // The relation still holds the team we just switched away from, and
        // anything reading it later this request — the settings cache key
        // included — would resolve to the wrong tenant.
        $user->unsetRelation('currentTeam');
        app('laravel-crm.settings')->forgetCache();

        $redirect = config('fortify.home') ?? route('laravel-crm.dashboard');

        return redirect($redirect, 303);
    }

    /**
     * Whether the host-app user belongs to the team being switched into.
     *
     * Jetstream's allTeams() first, the same portable signal TeamMembership and
     * SetApiTeamContext duck-type for. TeamMembership::inCurrentTeam() cannot be
     * reused here: it asks about the current team, and the team that matters
     * here is the target.
     *
     * allTeams() alone would leave this guard inert, though. It comes from
     * Jetstream's HasTeams trait — and so does switchTeam(), so any user
     * reaching this branch at all is one HasTeams did not apply to. The
     * fallback matters more than the primary path: without Jetstream,
     * resolveTeamModel() hands back a CRM team, which makes crm_team_user the
     * membership table for the very row being switched into rather than an
     * optional grouping.
     *
     * A user with no memberships recorded at all is unknowable — a host may
     * simply never have populated the pivot — so they are not blocked. Hosts
     * that do record memberships get the check.
     *
     * @param  mixed  $user  the host-app user being switched
     * @param  mixed  $team  the team being switched into
     */
    protected function isMemberOf($user, $team): bool
    {
        if (method_exists($user, 'allTeams')) {
            return collect($user->allTeams())
                ->contains(fn ($candidate) => (string) ($candidate->id ?? null) === (string) $team->id);
        }

        if ($team instanceof CrmTeam && method_exists($user, 'crmTeams')) {
            // crm_teams.user_id, read directly rather than through
            // ownsCrmTeam() — that derives the column from the user class name
            // via getForeignKey(), so it only lines up on hosts whose model is
            // called User.
            if ((string) $team->user_id === (string) $user->getKey()) {
                return true;
            }

            return $this->recordedCrmMemberships($user)->exists()
                ? $this->recordedCrmMemberships($user)->wherePivot('crm_team_id', $team->getKey())->exists()
                : true;
        }

        return true;
    }

    /**
     * The user's crm_team_user rows, with the tenancy scope taken off.
     *
     * crmTeams() resolves through Team, which carries BelongsToTeams — so left
     * alone it filters crm_teams down to the grouping the caller is currently
     * sitting in, which is the value they are asking to switch away from. Both
     * questions below then get answered relative to caller-influenced state:
     * a user whose pivot rows sit under a different (or null) crm_teams.team_id
     * reads as having no memberships at all and falls through the unknowable
     * branch into any team they like.
     *
     * Dropping the scope is safe because it is not what authorises this. The
     * target row already came from a scoped findOrFail(), so the host's own
     * tenancy rules decided which team is reachable; all that is left to settle
     * is whether the pivot records this user against it.
     *
     * Returns a fresh builder per call — exists() would otherwise leave its
     * constraints on a shared one.
     *
     * @param  mixed  $user
     */
    protected function recordedCrmMemberships($user)
    {
        return $user->crmTeams()->withoutGlobalScope(BelongsToTeamsScope::class);
    }

    protected function resolveTeamModel()
    {
        if (class_exists(Jetstream::class)) {
            return Jetstream::newTeamModel();
        }

        return new CrmTeam;
    }
}
