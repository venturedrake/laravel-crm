<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Portal;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use VentureDrake\LaravelCrm\Services\PortalAuthService;

class PortalAuthController extends Controller
{
    public function __construct(private PortalAuthService $portalAuth) {}

    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            return redirect()->intended($this->fallbackUrl());
        }

        if ($intended = $this->portalAuth->sanitizeIntended($request->query('intended'))) {
            $request->session()->put('url.intended', $intended);
        }

        return view('laravel-crm::portal.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (! $this->portalAuth->attemptLogin($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => trans('auth.failed')]);
        }

        $request->session()->regenerate();

        return redirect()->intended($this->fallbackUrl());
    }

    public function showRegister(Request $request)
    {
        abort_unless(config('laravel-crm.portal.allow_registration', false), 404);

        if (Auth::check()) {
            return redirect()->intended($this->fallbackUrl());
        }

        if ($intended = $this->portalAuth->sanitizeIntended($request->query('intended'))) {
            $request->session()->put('url.intended', $intended);
        }

        return view('laravel-crm::portal.auth.register');
    }

    public function register(Request $request)
    {
        abort_unless(config('laravel-crm.portal.allow_registration', false), 404);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $this->portalAuth->register($data);

        $request->session()->regenerate();

        return redirect()->intended($this->fallbackUrl());
    }

    public function logout(Request $request)
    {
        $this->portalAuth->logout($request);

        return redirect()->route('laravel-crm.portal.login');
    }

    private function fallbackUrl(): string
    {
        return route('laravel-crm.portal.features.index');
    }
}
