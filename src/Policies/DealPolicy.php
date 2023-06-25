<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\Deal;

class DealPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any deals.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm deals')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the deal.
     *
     * @param  \App\User  $user
     * @param  \App\Deal  $deal
     * @return mixed
     */
    public function view(User $user, Deal $deal)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm deals')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create deals.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('create crm deals')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the deal.
     *
     * @param  \App\User  $user
     * @param  \App\Deal  $deal
     * @return mixed
     */
    public function update(User $user, Deal $deal)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('edit crm deals')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the deal.
     *
     * @param  \App\User  $user
     * @param  \App\Deal  $deal
     * @return mixed
     */
    public function delete(User $user, Deal $deal)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('delete crm deals')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the deal.
     *
     * @param  \App\User  $user
     * @param  \App\Deal  $deal
     * @return mixed
     */
    public function restore(User $user, Deal $deal)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('delete crm deals')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the deal.
     *
     * @param  \App\User  $user
     * @param  \App\Deal  $deal
     * @return mixed
     */
    public function forceDelete(User $user, Deal $deal)
    {
        return false;
    }

    protected function isEnabled()
    {
        if(is_array(config('laravel-crm.modules')) && in_array('deals', config('laravel-crm.modules'))) {
            return true;
        } elseif(! config('laravel-crm.modules')) {
            return true;
        }
    }
}
