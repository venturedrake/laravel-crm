<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\Person;

class PersonPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any people.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasPermissionTo('view crm people')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the person.
     *
     * @param  \App\User  $user
     * @param  \App\Person  $person
     * @return mixed
     */
    public function view(User $user, Person $person)
    {
        if ($user->hasPermissionTo('view crm people')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create people.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasPermissionTo('create crm people')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the person.
     *
     * @param  \App\User  $user
     * @param  \App\Person  $person
     * @return mixed
     */
    public function update(User $user, Person $person)
    {
        if ($user->hasPermissionTo('edit crm people')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the person.
     *
     * @param  \App\User  $user
     * @param  \App\Person  $person
     * @return mixed
     */
    public function delete(User $user, Person $person)
    {
        if ($user->hasPermissionTo('delete crm people')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the person.
     *
     * @param  \App\User  $user
     * @param  \App\Person  $person
     * @return mixed
     */
    public function restore(User $user, Person $person)
    {
        if ($user->hasPermissionTo('delete crm people')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the person.
     *
     * @param  \App\User  $user
     * @param  \App\Person  $person
     * @return mixed
     */
    public function forceDelete(User $user, Person $person)
    {
        return true;
    }
}
