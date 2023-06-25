<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\Order;

class OrderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any orders.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm orders')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the order.
     *
     * @param  \App\User  $user
     * @param  \App\Order  $order
     * @return mixed
     */
    public function view(User $user, Order $order)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm orders')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create orders.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('create crm orders')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the order.
     *
     * @param  \App\User  $user
     * @param  \App\Order  $order
     * @return mixed
     */
    public function update(User $user, Order $order)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('edit crm orders')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the order.
     *
     * @param  \App\User  $user
     * @param  \App\Order  $order
     * @return mixed
     */
    public function delete(User $user, Order $order)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('delete crm orders')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the order.
     *
     * @param  \App\User  $user
     * @param  \App\Order  $order
     * @return mixed
     */
    public function restore(User $user, Order $order)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('delete crm orders')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the order.
     *
     * @param  \App\User  $user
     * @param  \App\Order  $order
     * @return mixed
     */
    public function forceDelete(User $user, Order $order)
    {
        return false;
    }

    protected function isEnabled()
    {
        if(is_array(config('laravel-crm.modules')) && in_array('orders', config('laravel-crm.modules'))) {
            return true;
        } elseif(! config('laravel-crm.modules')) {
            return true;
        }
    }
}
