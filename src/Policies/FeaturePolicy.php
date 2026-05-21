<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\Feature;

class FeaturePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any features.
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm features')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the feature.
     *
     * @return mixed
     */
    public function view(User $user, Feature $feature)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm features')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create features.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('create crm features')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the feature.
     *
     * @return mixed
     */
    public function update(User $user, Feature $feature)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('edit crm features')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the feature.
     *
     * @return mixed
     */
    public function delete(User $user, Feature $feature)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('delete crm features')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the feature.
     *
     * @return mixed
     */
    public function restore(User $user, Feature $feature)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('delete crm features')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the feature.
     *
     * @return mixed
     */
    public function forceDelete(User $user, Feature $feature)
    {
        return false;
    }

    /**
     * Determine whether the user can manage feature statuses.
     *
     * @return mixed
     */
    public function manageStatuses(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('manage crm feature statuses')) {
            return true;
        }
    }

    protected function isEnabled()
    {
        if (is_array(config('laravel-crm.modules')) && in_array('features', config('laravel-crm.modules'))) {
            return true;
        } elseif (! config('laravel-crm.modules')) {
            return true;
        }
    }
}
