<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\FieldOption;

class FieldOptionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any fieldOptions.
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
     * Determine whether the user can view the fieldOption.
     *
     * @param  \App\User  $user
     * @param  \App\FieldOption  $fieldOption
     * @return mixed
     */
    public function view(User $user, FieldOption $fieldOption)
    {
        if ($user->hasPermissionTo('view crm fields')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create fieldOptions.
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
     * Determine whether the user can update the fieldOption.
     *
     * @param  \App\User  $user
     * @param  \App\FieldOption  $fieldOption
     * @return mixed
     */
    public function update(User $user, FieldOption $fieldOption)
    {
        if ($fieldOption->system != 1 && $user->hasPermissionTo('edit crm fields')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the fieldOption.
     *
     * @param  \App\User  $user
     * @param  \App\FieldOption  $fieldOption
     * @return mixed
     */
    public function delete(User $user, FieldOption $fieldOption)
    {
        if ($fieldOption->system != 1 && $user->hasPermissionTo('delete crm fields')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the fieldOption.
     *
     * @param  \App\User  $user
     * @param  \App\FieldOption  $fieldOption
     * @return mixed
     */
    public function restore(User $user, FieldOption $fieldOption)
    {
        if ($fieldOption->system != 1 && $user->hasPermissionTo('delete crm fields')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the fieldOption.
     *
     * @param  \App\User  $user
     * @param  \App\FieldOption  $fieldOption
     * @return mixed
     */
    public function forceDelete(User $user, FieldOption $fieldOption)
    {
        return false;
    }
}
