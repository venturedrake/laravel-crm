<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\ChatConversation;

class ChatConversationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        if ($user->hasPermissionTo('view crm chat')) {
            return true;
        }
    }

    public function view(User $user, ChatConversation $conversation)
    {
        if ($user->hasPermissionTo('view crm chat')) {
            return true;
        }
    }

    public function reply(User $user, ChatConversation $conversation)
    {
        if ($user->hasPermissionTo('reply crm chat')) {
            return true;
        }
    }

    public function update(User $user, ChatConversation $conversation)
    {
        if ($user->hasPermissionTo('reply crm chat')) {
            return true;
        }
    }

    public function delete(User $user, ChatConversation $conversation)
    {
        if ($user->hasPermissionTo('delete crm chat')) {
            return true;
        }
    }
}

