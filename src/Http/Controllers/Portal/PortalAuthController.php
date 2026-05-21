<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Portal;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PortalAuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            return redirect()->intended(url('/'));
        }

        if ($intended = $request->query('intended')) {
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

        if (! Auth::attempt($credentials, (bool) $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => trans('auth.failed')]);
        }

        $request->session()->regenerate();

        return redirect()->intended(url('/'));
    }

    public function showRegister(Request $request)
    {
        if (Auth::check()) {
            return redirect()->intended(url('/'));
        }

        if ($intended = $request->query('intended')) {
            $request->session()->put('url.intended', $intended);
        }

        return view('laravel-crm::portal.auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $userModel = config('auth.providers.users.model');

        $user = $userModel::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'crm_access' => 0,
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->intended(url('/'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('laravel-crm.portal.login');
    }
}
