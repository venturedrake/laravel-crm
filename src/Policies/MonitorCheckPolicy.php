<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\MonitorCheck;

class MonitorCheckPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any monitor checks.
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return (new MonitorPolicy)->viewAny($user);
    }

    /**
     * Determine whether the user can view the monitor check.
     *
     * @param  \App\MonitorCheck  $monitorCheck
     * @return mixed
     */
    public function view(User $user, MonitorCheck $monitorCheck)
    {
        return (new MonitorPolicy)->view($user, $monitorCheck->monitor);
    }

    /**
     * Determine whether the user can create monitor checks.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return (new MonitorPolicy)->create($user);
    }

    /**
     * Determine whether the user can update the monitor check.
     *
     * @param  \App\MonitorCheck  $monitorCheck
     * @return mixed
     */
    public function update(User $user, MonitorCheck $monitorCheck)
    {
        return (new MonitorPolicy)->update($user, $monitorCheck->monitor);
    }

    /**
     * Determine whether the user can delete the monitor check.
     *
     * @param  \App\MonitorCheck  $monitorCheck
     * @return mixed
     */
    public function delete(User $user, MonitorCheck $monitorCheck)
    {
        return (new MonitorPolicy)->delete($user, $monitorCheck->monitor);
    }

    /**
     * Determine whether the user can restore the monitor check.
     *
     * @param  \App\MonitorCheck  $monitorCheck
     * @return mixed
     */
    public function restore(User $user, MonitorCheck $monitorCheck)
    {
        return (new MonitorPolicy)->restore($user, $monitorCheck->monitor);
    }

    /**
     * Determine whether the user can permanently delete the monitor check.
     *
     * @param  \App\MonitorCheck  $monitorCheck
     * @return mixed
     */
    public function forceDelete(User $user, MonitorCheck $monitorCheck)
    {
        return false;
    }
}
