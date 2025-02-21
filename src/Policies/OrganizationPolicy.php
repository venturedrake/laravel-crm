<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\Organization;

class OrganizationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any organizations.
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasPermissionTo('view crm organizations')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the organization.
     *
     * @param  \App\Organization  $organization
     * @return mixed
     */
    public function view(User $user, Organization $organization)
    {
        if ($user->hasPermissionTo('view crm organizations')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create organizations.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasPermissionTo('create crm organizations')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the organization.
     *
     * @param  \App\Organization  $organization
     * @return mixed
     */
    public function update(User $user, Organization $organization)
    {
        if ($user->hasPermissionTo('edit crm organizations')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the organization.
     *
     * @param  \App\Organization  $organization
     * @return mixed
     */
    public function delete(User $user, Organization $organization)
    {
        if ($user->hasPermissionTo('delete crm organizations')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the organization.
     *
     * @param  \App\Organization  $organization
     * @return mixed
     */
    public function restore(User $user, Organization $organization)
    {
        if ($user->hasPermissionTo('delete crm organizations')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the organization.
     *
     * @param  \App\Organization  $organization
     * @return mixed
     */
    public function forceDelete(User $user, Organization $organization)
    {
        return false;
    }
}
