<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\ProductCategory;

class ProductCategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any product categories.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasPermissionTo('view crm product categories')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the product category.
     *
     * @param  \App\User  $user
     * @param  \App\ProductCategory  $productCategory
     * @return mixed
     */
    public function view(User $user, ProductCategory $productCategory)
    {
        if ($user->hasPermissionTo('view crm product categories')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create product categories.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasPermissionTo('create crm product categories')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the product category.
     *
     * @param  \App\User  $user
     * @param  \App\ProductCategory  $productCategory
     * @return mixed
     */
    public function update(User $user, ProductCategory $productCategory)
    {
        if ($user->hasPermissionTo('edit crm product categories')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the product category.
     *
     * @param  \App\User  $user
     * @param  \App\ProductCategory  $productCategory
     * @return mixed
     */
    public function delete(User $user, ProductCategory $productCategory)
    {
        if ($user->hasPermissionTo('delete crm product categories')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the product category.
     *
     * @param  \App\User  $user
     * @param  \App\ProductCategory  $productCategory
     * @return mixed
     */
    public function restore(User $user, ProductCategory $productCategory)
    {
        if ($user->hasPermissionTo('delete crm product categories')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the product category.
     *
     * @param  \App\User  $user
     * @param  \App\ProductCategory  $productCategory
     * @return mixed
     */
    public function forceDelete(User $user, ProductCategory $productCategory)
    {
        return false;
    }
}
