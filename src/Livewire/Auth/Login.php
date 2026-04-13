<?php

namespace VentureDrake\LaravelCrm\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('laravel-crm::layouts.auth')]
class Login extends Component
{
    use Toast;

    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    protected function rules()
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string',
        ];
    }

    public function login()
    {
        $this->validate();

        $throttleKey = Str::transliterate(Str::lower($this->email).'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            $this->error(
                trans('auth.throttle', ['seconds' => $seconds])
            );

            return;
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($throttleKey);

            $this->error(
                trans('auth.failed')
            );

            return;
        }

        RateLimiter::clear($throttleKey);

        session()->regenerate();

        $this->redirect(route('laravel-crm.dashboard'));
    }

    public function render()
    {
        return view('laravel-crm::livewire.auth.login');
    }
}
