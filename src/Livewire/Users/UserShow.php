<?php

namespace VentureDrake\LaravelCrm\Livewire\Users;

use App\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Support\TeamMembership;

class UserShow extends Component
{
    use AuthorizesRequests, Toast;

    public User $user;

    public string $dateFormat = 'Y-m-d';

    public string $timeFormat = 'H:i';

    public function mount(): void
    {
        $settings = app('laravel-crm.settings');
        $this->dateFormat = $settings->get('date_format', config('laravel-crm.date_format', 'Y-m-d'));
        $this->timeFormat = $settings->get('time_format', config('laravel-crm.time_format', 'H:i'));
    }

    public function delete($id)
    {
        $user = User::find($id);

        if (! $user) {
            return;
        }

        $this->authorize('delete', $user);

        // Self-deletion is a user error, not a permission failure -- see UserIndex::delete().
        if ((int) $user->getKey() === (int) auth()->id()) {
            $this->error(ucfirst(trans('laravel-crm::lang.user_cannot_delete_self')));

            return;
        }

        abort_unless(TeamMembership::inCurrentTeam($user), 403);

        $user->deleteOrFail();

        $this->success(
            ucfirst(trans('laravel-crm::lang.user_deleted')),
            redirectTo: route('laravel-crm.users.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.users.user-show');
    }
}
