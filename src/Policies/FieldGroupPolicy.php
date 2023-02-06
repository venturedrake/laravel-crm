<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\FieldGroup;

class FieldGroupPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any fieldGroups.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasPermissionTo('view crm fields')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the fieldGroup.
     *
     * @param  \App\User  $user
     * @param  \App\FieldGroup  $fieldGroup
     * @return mixed
     */
    public function view(User $user, FieldGroup $fieldGroup)
    {
        if ($user->hasPermissionTo('view crm fields')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create fieldGroups.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasPermissionTo('create crm fields')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the fieldGroup.
     *
     * @param  \App\User  $user
     * @param  \App\FieldGroup  $fieldGroup
     * @return mixed
     */
    public function update(User $user, FieldGroup $fieldGroup)
    {
        if ($fieldGroup->system != 1 && $user->hasPermissionTo('edit crm fields')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the fieldGroup.
     *
     * @param  \App\User  $user
     * @param  \App\FieldGroup  $fieldGroup
     * @return mixed
     */
    public function delete(User $user, FieldGroup $fieldGroup)
    {
        if ($fieldGroup->system != 1 && $user->hasPermissionTo('delete crm fields')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the fieldGroup.
     *
     * @param  \App\User  $user
     * @param  \App\FieldGroup  $fieldGroup
     * @return mixed
     */
    public function restore(User $user, FieldGroup $fieldGroup)
    {
        if ($fieldGroup->system != 1 && $user->hasPermissionTo('delete crm fields')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the fieldGroup.
     *
     * @param  \App\User  $user
     * @param  \App\FieldGroup  $fieldGroup
     * @return mixed
     */
    public function forceDelete(User $user, FieldGroup $fieldGroup)
    {
        return false;
    }
}
