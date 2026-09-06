<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Portal;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use VentureDrake\LaravelCrm\Models\Feature;
use VentureDrake\LaravelCrm\Services\FeatureService;
use VentureDrake\LaravelCrm\Support\PortalTeam;

class PublicFeatureController extends Controller
{
    /**
     * Serves both `/p/features` and `/p/features/team/{portalTeam}`.
     *
     * Resolving here rather than in the Livewire component means the team is
     * settled — and remembered in the session — before the board renders, so
     * the component and every link on the page agree on which board this is.
     */
    public function index(?int $portalTeam = null)
    {
        $teamId = PortalTeam::resolve($portalTeam);

        abort_if(PortalTeam::scoped() && $teamId === null, 404);

        // Handed to the component rather than left for it to re-derive: the
        // session is shared across tabs, so a visitor with two boards open
        // would otherwise see one of them re-resolve onto the other's team on
        // its next Livewire update.
        return view('laravel-crm::portal.features.index', ['portalTeamId' => $teamId]);
    }

    public function show(Request $request, Feature $feature, FeatureService $featureService)
    {
        abort_unless($feature->is_public, 404);
        $this->ensurePortalTeam($feature);

        $featureService->recordView($feature, Auth::user(), $request->ip());

        return view('laravel-crm::portal.features.show', compact('feature'));
    }

    public function create()
    {
        if ($redirect = $this->requireAuth(route('laravel-crm.portal.features.create'))) {
            return $redirect;
        }

        // Fail here rather than on submit, so nobody types a request into a
        // form that has no board to post it to.
        abort_if(PortalTeam::scoped() && PortalTeam::resolve() === null, 404);

        return view('laravel-crm::portal.features.submit');
    }

    public function store(Request $request, FeatureService $featureService)
    {
        if ($redirect = $this->requireAuth(route('laravel-crm.portal.features.create'))) {
            return $redirect;
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
        ]);

        $payload = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'is_public' => true,
        ];

        if (PortalTeam::scoped()) {
            $portalTeamId = PortalTeam::resolve();
            abort_if($portalTeamId === null, 404);

            // Stamped with the board's team, not the submitter's. The people a
            // public roadmap is for are customers who registered through
            // /p/register — they hold no host-app team, so requiring their
            // currentTeam to match the board 403'd exactly the users the
            // portal exists to serve.
            $payload['team_id'] = $portalTeamId;
        }

        $feature = $featureService->create($payload, Auth::user());

        return redirect()
            ->route('laravel-crm.portal.features.show', $feature->external_id)
            ->with('status', 'feature_submitted');
    }

    public function vote(Feature $feature, FeatureService $featureService)
    {
        abort_unless($feature->is_public, 404);
        $this->ensurePortalTeam($feature);

        if ($redirect = $this->requireAuth(route('laravel-crm.portal.features.show', $feature->external_id))) {
            return $redirect;
        }

        $featureService->vote($feature, Auth::user());

        return redirect()->route('laravel-crm.portal.features.show', $feature->external_id);
    }

    public function unvote(Feature $feature, FeatureService $featureService)
    {
        abort_unless($feature->is_public, 404);
        $this->ensurePortalTeam($feature);

        if ($redirect = $this->requireAuth(route('laravel-crm.portal.features.show', $feature->external_id))) {
            return $redirect;
        }

        $featureService->unvote($feature, Auth::user());

        return redirect()->route('laravel-crm.portal.features.show', $feature->external_id);
    }

    public function comment(Request $request, Feature $feature, FeatureService $featureService)
    {
        abort_unless($feature->is_public, 404);
        $this->ensurePortalTeam($feature);

        if ($redirect = $this->requireAuth(route('laravel-crm.portal.features.show', $feature->external_id))) {
            return $redirect;
        }

        $data = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        // Portal posts are always treated as public-user comments, even if the
        // authenticated session belongs to a CRM admin who happens to be
        // browsing the public board.
        $featureService->comment($feature, Auth::user(), $data['body'], isAdminReply: false);

        return redirect()->route('laravel-crm.portal.features.show', $feature->external_id);
    }

    private function requireAuth(string $intended)
    {
        if (Auth::check()) {
            return null;
        }

        // `intended` is always built from a server-side route() above; no need to re-sanitize.
        session()->put('url.intended', $intended);

        return redirect()->route('laravel-crm.portal.login', ['intended' => $intended]);
    }

    /**
     * A public feature is reachable by its own link whoever owns it — that is
     * what makes a shared roadmap link work for someone with no account — and
     * opening one moves the visitor onto that team's board for the rest of the
     * session.
     */
    private function ensurePortalTeam(Feature $feature): void
    {
        if (! PortalTeam::scoped()) {
            return;
        }

        // A teamless feature belongs to no board, so it is on none of them.
        abort_if($feature->team_id === null, 404);

        PortalTeam::adopt((int) $feature->team_id);
    }
}
