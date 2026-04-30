<?php

namespace VentureDrake\LaravelCrm\Livewire\Profile;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Mary\Traits\Toast;

class DeleteUserForm extends Component
{
    use Toast;

    public $confirmingUserDeletion = false;

    public $password = '';

    public function confirmUserDeletion()
    {
        $this->password = '';
        $this->confirmingUserDeletion = true;
    }

    public function deleteUser()
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        $user = auth()->user();

        Auth::logout();

        $user->delete();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $this->redirect(url('/'));
    }

    public function render()
    {
        return view('laravel-crm::livewire.profile.delete-user-form');
    }
}
