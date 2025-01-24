<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\Organization;

class OrganisationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any organisations.
     *
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
     * @param  \App\Organisation  $organisation
     * @return mixed
     */
    public function view(User $user, Organization $organisation)
    {
        if ($user->hasPermissionTo('view crm organisations')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create organisations.
     *
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
     * @param  \App\Organisation  $organisation
     * @return mixed
     */
    public function update(User $user, Organization $organisation)
    {
        if ($user->hasPermissionTo('edit crm organisations')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the organisation.
     *
     * @param  \App\Organisation  $organisation
     * @return mixed
     */
    public function delete(User $user, Organization $organisation)
    {
        if ($user->hasPermissionTo('delete crm organisations')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the organisation.
     *
     * @param  \App\Organisation  $organisation
     * @return mixed
     */
    public function restore(User $user, Organization $organisation)
    {
        if ($user->hasPermissionTo('delete crm organisations')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the organisation.
     *
     * @param  \App\Organisation  $organisation
     * @return mixed
     */
    public function forceDelete(User $user, Organization $organisation)
    {
        return false;
    }
}
