<?php

namespace VentureDrake\LaravelCrm\Services;

use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

class PortalAuthService
{
    public function attemptLogin(array $credentials, bool $remember = false): bool
    {
        return Auth::attempt($credentials, $remember);
    }

    public function register(array $data)
    {
        $userModel = config('auth.providers.users.model');

        $user = $userModel::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'crm_access' => 0,
        ]);

        Event::dispatch(new Registered($user));

        Auth::login($user);

        return $user;
    }

    public function logout(Request $request): void
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /**
     * Only allow same-origin, portal-prefixed redirect targets (`/p/...`).
     */
    public function sanitizeIntended(?string $intended): ?string
    {
        if (! $intended) {
            return null;
        }

        $path = parse_url($intended, PHP_URL_PATH);

        if (! is_string($path) || ! str_starts_with($path, '/p/') && $path !== '/p') {
            return null;
        }

        $query = parse_url($intended, PHP_URL_QUERY);

        return $query ? $path.'?'.$query : $path;
    }
}
