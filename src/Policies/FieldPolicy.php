<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\Field;

class FieldPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any fields.
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
     * Determine whether the user can view the field.
     *
     * @param  \App\User  $user
     * @param  \App\Field  $field
     * @return mixed
     */
    public function view(User $user, Field $field)
    {
        if ($user->hasPermissionTo('view crm fields')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create fields.
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
     * Determine whether the user can update the field.
     *
     * @param  \App\User  $user
     * @param  \App\Field  $field
     * @return mixed
     */
    public function update(User $user, Field $field)
    {
        if ($field->system != 1 && $user->hasPermissionTo('edit crm fields')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the field.
     *
     * @param  \App\User  $user
     * @param  \App\Field  $field
     * @return mixed
     */
    public function delete(User $user, Field $field)
    {
        if ($field->system != 1 && $user->hasPermissionTo('delete crm fields')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the field.
     *
     * @param  \App\User  $user
     * @param  \App\Field  $field
     * @return mixed
     */
    public function restore(User $user, Field $field)
    {
        if ($field->system != 1 && $user->hasPermissionTo('delete crm fields')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the field.
     *
     * @param  \App\User  $user
     * @param  \App\Field  $field
     * @return mixed
     */
    public function forceDelete(User $user, Field $field)
    {
        //
    }
}
