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
     * An order with invoices, deliveries or purchase orders cannot be
     * deleted: those documents would be left pointing at nothing.
     *
     * @param  \App\Order  $order
     * @return mixed
     */
    public function delete(User $user, Order $order)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('delete crm orders')) {
            if (! $order->canBeDeleted()) {
                return $this->deny($order->deleteBlockedReason());
            }

            return true;
        }
    }

    /**
     * Determine whether the user can restore the order.
     *
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
     * @param  \App\Order  $order
     * @return mixed
     */
    public function forceDelete(User $user, Order $order)
    {
        return false;
    }

    /**
     * Determine whether the user can manage the products (line items) on orders.
     *
     * Gated on the order permission rather than the product permission: line-item
     * editing is part of building an order, and neither Manager nor Employee holds
     * any 'crm products' permission.
     *
     * @return mixed
     */
    public function manageProducts(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('edit crm orders')) {
            return true;
        }
    }

    protected function isEnabled()
    {
        if (is_array(config('laravel-crm.modules')) && in_array('orders', config('laravel-crm.modules'))) {
            return true;
        } elseif (! config('laravel-crm.modules')) {
            return true;
        }
    }
}
