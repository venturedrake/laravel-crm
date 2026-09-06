<?php

namespace VentureDrake\LaravelCrm\Support;

use VentureDrake\LaravelCrm\Models\Feature;

/**
 * Works out which team's public portal the current request is looking at.
 *
 * On a `laravel-crm.teams` install every team has its own public board. The
 * portal is anonymous by definition — the people reading a roadmap are the
 * team's customers, not its staff — so the team cannot come from
 * `auth()->user()->currentTeam` alone.
 *
 * Resolution order:
 *
 *  1. The team named in the URL (`/p/features/team/{id}`), which is what makes
 *     a board shareable with people who have no account.
 *  2. The team remembered in the session from (1) or from a feature page the
 *     visitor already opened, so "back to the board", voting and submitting
 *     stay on the board they arrived at.
 *  3. The signed-in user's current team — the natural default for staff.
 *  4. The only team that has a public board, when there is exactly one. This
 *     is what makes the common "teams enabled, one team" install work with no
 *     configuration and no team in the URL.
 *
 * Returns null only when teams are enabled and none of the above answered, at
 * which point the caller should 404 rather than guess.
 *
 * There is deliberately no configured override. `LARAVEL_CRM_PORTAL_TEAM_ID`
 * used to sit ahead of all four as a hard single-tenant lock, which was
 * harmless while the portal only served roadmaps but wrong the moment
 * forDocument() started answering "whose branding does this invoice carry?" —
 * a document states its own owner, and one env var was silently overruling it
 * for every team on the install. Every signal above is now derived from the
 * request or the record.
 */
class PortalTeam
{
    public const SESSION_KEY = 'laravel-crm.portal_team_id';

    /**
     * Is the portal team-scoped at all? False on a single-tenant install,
     * where every caller should skip team filtering entirely.
     */
    public static function scoped(): bool
    {
        return (bool) config('laravel-crm.teams');
    }

    /**
     * Resolve the team whose board this request should show.
     *
     * @param  int|null  $fromUrl  the team id taken from the route, when the
     *                             request addressed one explicitly
     */
    public static function resolve(?int $fromUrl = null): ?int
    {
        if (! static::scoped()) {
            return null;
        }

        if ($fromUrl !== null) {
            static::remember($fromUrl);

            return $fromUrl;
        }

        if ($remembered = static::remembered()) {
            return $remembered;
        }

        if (($user = auth()->user()) && ($team = $user->currentTeam ?? null)) {
            return (int) $team->id;
        }

        return static::soleBoardTeamId();
    }

    /**
     * The team a signed-URL document belongs to.
     *
     * Unlike resolve(), nothing about the visitor is consulted: a quote or
     * invoice link is authorised by its signature and carries its own team, so
     * the branding on the page follows the document rather than whoever
     * happens to be signed in. Returns null on a single-tenant install (no
     * scoping to do) and for a document that predates teams.
     *
     * Nothing about the install overrides it either: the document's own
     * team_id is the only answer, because anything else is another tenant's
     * organisation name, ABN and logo printed on this tenant's invoice.
     */
    public static function forDocument($model): ?int
    {
        if (! static::scoped()) {
            return null;
        }

        return $model->team_id === null ? null : (int) $model->team_id;
    }

    /**
     * Take the team from a feature the visitor has navigated to, and remember
     * it so the rest of the portal follows them onto that board.
     *
     * A public feature is public: it is reachable by its own link whichever
     * team owns it.
     *
     * Returns nothing on purpose. This used to hand back the team actually
     * adopted, because the configured lock could answer with a different one
     * than the caller offered — and both call sites turned that into an
     * `abort_if(adopt($id) !== $id, 404)`. With the lock gone it can only ever
     * return its own argument, so keeping the return value would leave two
     * guards that read as tenancy checks on public routes and enforce nothing.
     * Adopting a board is a side effect; the caller decides what is reachable.
     */
    public static function adopt(?int $teamId): void
    {
        if (! static::scoped() || $teamId === null) {
            return;
        }

        static::remember($teamId);
    }

    /**
     * Store the visitor's current board for subsequent requests.
     */
    public static function remember(int $teamId): void
    {
        session()->put(static::SESSION_KEY, $teamId);
    }

    /**
     * The board remembered from an earlier request in this session.
     */
    public static function remembered(): ?int
    {
        $teamId = session(static::SESSION_KEY);

        return $teamId === null ? null : (int) $teamId;
    }

    /**
     * The only team with a public board, or null when there are none or more
     * than one.
     *
     * Scopes are dropped deliberately: this runs for anonymous visitors, where
     * BelongsToTeamsScope is inert anyway, and for signed-in staff, where it
     * would otherwise narrow the count to their own team and make every
     * install look single-team.
     */
    protected static function soleBoardTeamId(): ?int
    {
        $teamIds = Feature::query()
            ->withoutGlobalScopes()
            ->public()
            ->whereNotNull('team_id')
            ->distinct()
            ->limit(2)
            ->pluck('team_id');

        return $teamIds->count() === 1 ? (int) $teamIds->first() : null;
    }
}
