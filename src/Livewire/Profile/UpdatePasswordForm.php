<?php

namespace VentureDrake\LaravelCrm\Livewire\Profile;

use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Mary\Traits\Toast;

class UpdatePasswordForm extends Component
{
    use Toast;

    public $current_password = '';

    public $password = '';

    public $password_confirmation = '';

    protected function rules()
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function updatePassword()
    {
        $this->validate();

        auth()->user()->forceFill([
            'password' => Hash::make($this->password),
        ])->save();

        $this->reset(['current_password', 'password', 'password_confirmation']);

        $this->success(ucfirst(__('laravel-crm::lang.password_updated')));
    }

    public function render()
    {
        return view('laravel-crm::livewire.profile.update-password-form');
    }
}
