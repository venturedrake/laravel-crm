<?php

namespace VentureDrake\LaravelCrm\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('laravel-crm::layouts.auth')]
class ForgotPassword extends Component
{
    use Toast;

    public string $email = '';

    protected function rules()
    {
        return [
            'email' => 'required|email',
        ];
    }

    public function sendResetLink()
    {
        $this->validate();

        $status = Password::sendResetLink(['email' => $this->email]);

        if ($status === Password::RESET_LINK_SENT) {
            $this->email = '';

            $this->success(
                ucfirst(trans('laravel-crm::lang.password_reset_link_sent'))
            );

            return;
        }

        $this->error(
            trans($status)
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.auth.forgot-password');
    }
}
