<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\Monitor;

class MonitorPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any monitors.
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm monitors')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the monitor.
     *
     * @param  \App\Monitor  $monitor
     * @return mixed
     */
    public function view(User $user, Monitor $monitor)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm monitors')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create monitors.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('create crm monitors')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the monitor.
     *
     * @param  \App\Monitor  $monitor
     * @return mixed
     */
    public function update(User $user, Monitor $monitor)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('edit crm monitors')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the monitor.
     *
     * @param  \App\Monitor  $monitor
     * @return mixed
     */
    public function delete(User $user, Monitor $monitor)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('delete crm monitors')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the monitor.
     *
     * @param  \App\Monitor  $monitor
     * @return mixed
     */
    public function restore(User $user, Monitor $monitor)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('delete crm monitors')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the monitor.
     *
     * @param  \App\Monitor  $monitor
     * @return mixed
     */
    public function forceDelete(User $user, Monitor $monitor)
    {
        return false;
    }

    protected function isEnabled()
    {
        if (is_array(config('laravel-crm.modules')) && in_array('monitoring', config('laravel-crm.modules'))) {
            return true;
        } elseif (! config('laravel-crm.modules')) {
            return true;
        }
    }
}
