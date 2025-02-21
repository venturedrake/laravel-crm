<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\Customer;

class CustomerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any customers.
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasPermissionTo('view crm customers')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the customer.
     *
     * @param  \App\Customer  $customer
     * @return mixed
     */
    public function view(User $user, Customer $customer)
    {
        if ($user->hasPermissionTo('view crm customers')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create customers.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasPermissionTo('create crm customers')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the customer.
     *
     * @param  \App\Customer  $customer
     * @return mixed
     */
    public function update(User $user, Customer $customer)
    {
        if ($user->hasPermissionTo('edit crm customers')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the customer.
     *
     * @param  \App\Customer  $customer
     * @return mixed
     */
    public function delete(User $user, Customer $customer)
    {
        if ($user->hasPermissionTo('delete crm customers')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the customer.
     *
     * @param  \App\Customer  $customer
     * @return mixed
     */
    public function restore(User $user, Customer $customer)
    {
        if ($user->hasPermissionTo('delete crm customers')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the customer.
     *
     * @param  \App\Customer  $customer
     * @return mixed
     */
    public function forceDelete(User $user, Customer $customer)
    {
        return true;
    }
}
