<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\ProductAttribute;

class ProductAttributePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any product attributes.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasPermissionTo('view crm product attributes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the product attribute.
     *
     * @param  \App\User  $user
     * @param  \App\ProductAttribute  $productAttribute
     * @return mixed
     */
    public function view(User $user, ProductAttribute $productAttribute)
    {
        if ($user->hasPermissionTo('view crm product attributes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create product attributes.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasPermissionTo('create crm product attributes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the product attribute.
     *
     * @param  \App\User  $user
     * @param  \App\ProductAttribute  $productAttribute
     * @return mixed
     */
    public function update(User $user, ProductAttribute $productAttribute)
    {
        if ($user->hasPermissionTo('edit crm product attributes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the product attribute.
     *
     * @param  \App\User  $user
     * @param  \App\ProductAttribute  $productAttribute
     * @return mixed
     */
    public function delete(User $user, ProductAttribute $productAttribute)
    {
        if ($user->hasPermissionTo('delete crm product attributes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the product attribute.
     *
     * @param  \App\User  $user
     * @param  \App\ProductAttribute  $productAttribute
     * @return mixed
     */
    public function restore(User $user, ProductAttribute $productAttribute)
    {
        if ($user->hasPermissionTo('delete crm product attributes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the product attribute.
     *
     * @param  \App\User  $user
     * @param  \App\ProductAttribute  $productAttribute
     * @return mixed
     */
    public function forceDelete(User $user, ProductAttribute $productAttribute)
    {
        return false;
    }
}
