<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\Delivery;

class DeliveryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any deliveries.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm deliveries')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the delivery.
     *
     * @param  \App\User  $user
     * @param  \App\Delivery  $delivery
     * @return mixed
     */
    public function view(User $user, Delivery $delivery)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm deliveries')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create deliveries.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('create crm deliveries')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the delivery.
     *
     * @param  \App\User  $user
     * @param  \App\Delivery  $delivery
     * @return mixed
     */
    public function update(User $user, Delivery $delivery)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('edit crm deliveries')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the delivery.
     *
     * @param  \App\User  $user
     * @param  \App\Delivery  $delivery
     * @return mixed
     */
    public function delete(User $user, Delivery $delivery)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('delete crm deliveries')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the delivery.
     *
     * @param  \App\User  $user
     * @param  \App\Delivery  $delivery
     * @return mixed
     */
    public function restore(User $user, Delivery $delivery)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('delete crm deliveries')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the delivery.
     *
     * @param  \App\User  $user
     * @param  \App\Delivery  $delivery
     * @return mixed
     */
    public function forceDelete(User $user, Delivery $delivery)
    {
        return false;
    }

    protected function isEnabled()
    {
        if(is_array(config('laravel-crm.modules')) && in_array('deliveries', config('laravel-crm.modules'))) {
            return true;
        } elseif(! config('laravel-crm.modules')) {
            return true;
        }
    }
}
