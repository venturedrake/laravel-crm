<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;

class PurchaseOrderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any purchaseOrders.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm purchase orders')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the purchaseOrder.
     *
     * @param  \App\User  $user
     * @param  \App\PurchaseOrder  $purchaseOrder
     * @return mixed
     */
    public function view(User $user, PurchaseOrder $purchaseOrder)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm purchase orders')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create purchaseOrders.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('create crm purchase orders')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the purchaseOrder.
     *
     * @param  \App\User  $user
     * @param  \App\PurchaseOrder  $purchaseOrder
     * @return mixed
     */
    public function update(User $user, PurchaseOrder $purchaseOrder)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('edit crm purchase orders')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the purchaseOrder.
     *
     * @param  \App\User  $user
     * @param  \App\PurchaseOrder  $purchaseOrder
     * @return mixed
     */
    public function delete(User $user, PurchaseOrder $purchaseOrder)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('delete crm purchase orders')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the purchaseOrder.
     *
     * @param  \App\User  $user
     * @param  \App\PurchaseOrder  $purchaseOrder
     * @return mixed
     */
    public function restore(User $user, PurchaseOrder $purchaseOrder)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('delete crm purchase orders')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the purchaseOrder.
     *
     * @param  \App\User  $user
     * @param  \App\PurchaseOrder  $purchaseOrder
     * @return mixed
     */
    public function forceDelete(User $user, PurchaseOrder $purchaseOrder)
    {
        return false;
    }

    protected function isEnabled()
    {
        if(is_array(config('laravel-crm.modules')) && in_array('purchase-orders', config('laravel-crm.modules'))) {
            return true;
        } elseif(! config('laravel-crm.modules')) {
            return true;
        }
    }
}
