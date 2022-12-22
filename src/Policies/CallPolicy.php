<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\Call;

class CallPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any calls.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasPermissionTo('view crm calls')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the call.
     *
     * @param  \App\User  $user
     * @param  \App\Call  $call
     * @return mixed
     */
    public function view(User $user, Call $call)
    {
        if ($user->hasPermissionTo('view crm calls')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create calls.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasPermissionTo('create crm calls')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the call.
     *
     * @param  \App\User  $user
     * @param  \App\Call  $call
     * @return mixed
     */
    public function update(User $user, Call $call)
    {
        if ($user->hasPermissionTo('edit crm calls')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the call.
     *
     * @param  \App\User  $user
     * @param  \App\Call  $call
     * @return mixed
     */
    public function delete(User $user, Call $call)
    {
        if ($user->hasPermissionTo('delete crm calls')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the call.
     *
     * @param  \App\User  $user
     * @param  \App\Call  $call
     * @return mixed
     */
    public function restore(User $user, Call $call)
    {
        if ($user->hasPermissionTo('delete crm calls')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the call.
     *
     * @param  \App\User  $user
     * @param  \App\Call  $call
     * @return mixed
     */
    public function forceDelete(User $user, Call $call)
    {
        return false;
    }
}
