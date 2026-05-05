<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\ChatWidget;

class ChatWidgetPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        if ($user->hasPermissionTo('manage crm chat widgets')) {
            return true;
        }
    }

    public function view(User $user, ChatWidget $widget)
    {
        if ($user->hasPermissionTo('manage crm chat widgets')) {
            return true;
        }
    }

    public function create(User $user)
    {
        if ($user->hasPermissionTo('manage crm chat widgets')) {
            return true;
        }
    }

    public function update(User $user, ChatWidget $widget)
    {
        if ($user->hasPermissionTo('manage crm chat widgets')) {
            return true;
        }
    }

    public function delete(User $user, ChatWidget $widget)
    {
        if ($user->hasPermissionTo('manage crm chat widgets')) {
            return true;
        }
    }
}

