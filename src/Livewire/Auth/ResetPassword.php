<?php

namespace VentureDrake\LaravelCrm\Livewire\Auth;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('laravel-crm::layouts.auth')]
class ResetPassword extends Component
{
    use Toast;

    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token, ?string $email = null)
    {
        $this->token = $token;
        $this->email = $email ?? request()->query('email', '');
    }

    protected function rules()
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function resetPassword()
    {
        $this->validate();

        $status = Password::reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function ($user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            session()->flash('status', ucfirst(trans('laravel-crm::lang.password_reset_success')));

            $this->redirect(route('laravel-crm.login'));

            return;
        }

        $this->error(
            trans($status)
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.auth.reset-password');
    }
}
