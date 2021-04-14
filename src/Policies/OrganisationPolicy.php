<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\Organisation;

class OrganisationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any organisations.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasPermissionTo('view crm organisations')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the organisation.
     *
     * @param  \App\User  $user
     * @param  \App\Organisation  $organisation
     * @return mixed
     */
    public function view(User $user, Organisation $organisation)
    {
        if ($user->hasPermissionTo('view crm organisations')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create organisations.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasPermissionTo('create crm organisations')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the organisation.
     *
     * @param  \App\User  $user
     * @param  \App\Organisation  $organisation
     * @return mixed
     */
    public function update(User $user, Organisation $organisation)
    {
        if ($user->hasPermissionTo('edit crm organisations')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the organisation.
     *
     * @param  \App\User  $user
     * @param  \App\Organisation  $organisation
     * @return mixed
     */
    public function delete(User $user, Organisation $organisation)
    {
        if ($user->hasPermissionTo('delete crm organisations')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the organisation.
     *
     * @param  \App\User  $user
     * @param  \App\Organisation  $organisation
     * @return mixed
     */
    public function restore(User $user, Organisation $organisation)
    {
        if ($user->hasPermissionTo('delete crm organisations')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the organisation.
     *
     * @param  \App\User  $user
     * @param  \App\Organisation  $organisation
     * @return mixed
     */
    public function forceDelete(User $user, Organisation $organisation)
    {
        return false;
    }
}
