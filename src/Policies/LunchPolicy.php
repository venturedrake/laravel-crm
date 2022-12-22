<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\Lunch;

class LunchPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any lunchs.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasPermissionTo('view crm lunchs')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the lunch.
     *
     * @param  \App\User  $user
     * @param  \App\Lunch  $lunch
     * @return mixed
     */
    public function view(User $user, Lunch $lunch)
    {
        if ($user->hasPermissionTo('view crm lunchs')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create lunchs.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasPermissionTo('create crm lunchs')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the lunch.
     *
     * @param  \App\User  $user
     * @param  \App\Lunch  $lunch
     * @return mixed
     */
    public function update(User $user, Lunch $lunch)
    {
        if ($user->hasPermissionTo('edit crm lunchs')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the lunch.
     *
     * @param  \App\User  $user
     * @param  \App\Lunch  $lunch
     * @return mixed
     */
    public function delete(User $user, Lunch $lunch)
    {
        if ($user->hasPermissionTo('delete crm lunchs')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the lunch.
     *
     * @param  \App\User  $user
     * @param  \App\Lunch  $lunch
     * @return mixed
     */
    public function restore(User $user, Lunch $lunch)
    {
        if ($user->hasPermissionTo('delete crm lunchs')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the lunch.
     *
     * @param  \App\User  $user
     * @param  \App\Lunch  $lunch
     * @return mixed
     */
    public function forceDelete(User $user, Lunch $lunch)
    {
        return false;
    }
}
