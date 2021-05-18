<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\Product;

class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any products.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasPermissionTo('view crm products')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the product.
     *
     * @param  \App\User  $user
     * @param  \App\Product  $product
     * @return mixed
     */
    public function view(User $user, Product $product)
    {
        if ($user->hasPermissionTo('view crm products')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create products.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasPermissionTo('create crm products')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the product.
     *
     * @param  \App\User  $user
     * @param  \App\Product  $product
     * @return mixed
     */
    public function update(User $user, Product $product)
    {
        if ($user->hasPermissionTo('edit crm products')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the product.
     *
     * @param  \App\User  $user
     * @param  \App\Product  $product
     * @return mixed
     */
    public function delete(User $user, Product $product)
    {
        if ($user->hasPermissionTo('delete crm products')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the product.
     *
     * @param  \App\User  $user
     * @param  \App\Product  $product
     * @return mixed
     */
    public function restore(User $user, Product $product)
    {
        if ($user->hasPermissionTo('delete crm products')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the product.
     *
     * @param  \App\User  $user
     * @param  \App\Product  $product
     * @return mixed
     */
    public function forceDelete(User $user, Product $product)
    {
        //
    }
}
