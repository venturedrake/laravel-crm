<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Portal;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use VentureDrake\LaravelCrm\Models\Feature;
use VentureDrake\LaravelCrm\Services\FeatureService;

class PublicFeatureController extends Controller
{
    public function index()
    {
        return view('laravel-crm::portal.features.index');
    }

    public function show(Request $request, Feature $feature, FeatureService $featureService)
    {
        abort_unless($feature->is_public, 404);

        $featureService->recordView($feature, Auth::user(), $request->ip());

        return view('laravel-crm::portal.features.show', compact('feature'));
    }

    public function create()
    {
        if ($redirect = $this->requireAuth(route('laravel-crm.portal.features.create'))) {
            return $redirect;
        }

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

        $feature = $featureService->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'is_public' => true,
        ], Auth::user());

        return redirect()
            ->route('laravel-crm.portal.features.show', $feature->external_id)
            ->with('status', 'feature_submitted');
    }

    public function vote(Feature $feature, FeatureService $featureService)
    {
        abort_unless($feature->is_public, 404);

        if ($redirect = $this->requireAuth(route('laravel-crm.portal.features.show', $feature->external_id))) {
            return $redirect;
        }

        $featureService->vote($feature, Auth::user());

        return redirect()->route('laravel-crm.portal.features.show', $feature->external_id);
    }

    public function unvote(Feature $feature, FeatureService $featureService)
    {
        abort_unless($feature->is_public, 404);

        if ($redirect = $this->requireAuth(route('laravel-crm.portal.features.show', $feature->external_id))) {
            return $redirect;
        }

        $featureService->unvote($feature, Auth::user());

        return redirect()->route('laravel-crm.portal.features.show', $feature->external_id);
    }

    public function comment(Request $request, Feature $feature, FeatureService $featureService)
    {
        abort_unless($feature->is_public, 404);

        if ($redirect = $this->requireAuth(route('laravel-crm.portal.features.show', $feature->external_id))) {
            return $redirect;
        }

        $data = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $featureService->comment($feature, Auth::user(), $data['body']);

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
}
