<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\Position;
use VentureDrake\LaravelCrm\Support\Modules;

/**
 * Positions have no permissions of their own. A position is part of a person's
 * record, so reading one needs `view crm people` and changing one needs
 * `edit crm people`. Every ability is denied while the `positions` module is
 * disabled.
 */
class PositionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any positions.
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm people')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the position.
     *
     * @return mixed
     */
    public function view(User $user, Position $position)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm people')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create positions.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('edit crm people')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the position.
     *
     * @return mixed
     */
    public function update(User $user, Position $position)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('edit crm people')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the position.
     *
     * @return mixed
     */
    public function delete(User $user, Position $position)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('edit crm people')) {
            return true;
        }
    }

    protected function isEnabled()
    {
        return Modules::enabled('positions');
    }
}
