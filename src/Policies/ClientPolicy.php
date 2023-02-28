<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\Person;

class ClientPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any clients.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasPermissionTo('view crm clients')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the client.
     *
     * @param  \App\User  $user
     * @param  \App\Person  $client
     * @return mixed
     */
    public function view(User $user, Person $client)
    {
        if ($user->hasPermissionTo('view crm clients')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create clients.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasPermissionTo('create crm clients')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the client.
     *
     * @param  \App\User  $user
     * @param  \App\Person  $client
     * @return mixed
     */
    public function update(User $user, Person $client)
    {
        if ($user->hasPermissionTo('edit crm clients')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the client.
     *
     * @param  \App\User  $user
     * @param  \App\Person  $client
     * @return mixed
     */
    public function delete(User $user, Person $client)
    {
        if ($user->hasPermissionTo('delete crm clients')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the client.
     *
     * @param  \App\User  $user
     * @param  \App\Person  $client
     * @return mixed
     */
    public function restore(User $user, Person $client)
    {
        if ($user->hasPermissionTo('delete crm clients')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the client.
     *
     * @param  \App\User  $user
     * @param  \App\Person  $client
     * @return mixed
     */
    public function forceDelete(User $user, Person $client)
    {
        return true;
    }
}
