<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\TaxRate;

class TaxRatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any tax rates.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasPermissionTo('view crm tax rates')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view thetax rate.
     *
     * @param  \App\User  $user
     * @param  \App\TaxRate  $taxRate
     * @return mixed
     */
    public function view(User $user, TaxRate $taxRate)
    {
        if ($user->hasPermissionTo('view crm tax rates')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create tax rates.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasPermissionTo('create crm tax rates')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update thetax rate.
     *
     * @param  \App\User  $user
     * @param  \App\TaxRate  $taxRate
     * @return mixed
     */
    public function update(User $user, TaxRate $taxRate)
    {
        if ($user->hasPermissionTo('edit crm tax rates')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete thetax rate.
     *
     * @param  \App\User  $user
     * @param  \App\TaxRate  $taxRate
     * @return mixed
     */
    public function delete(User $user, TaxRate $taxRate)
    {
        if ($user->hasPermissionTo('delete crm tax rates')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore thetax rate.
     *
     * @param  \App\User  $user
     * @param  \App\TaxRate  $taxRate
     * @return mixed
     */
    public function restore(User $user, TaxRate $taxRate)
    {
        if ($user->hasPermissionTo('delete crm tax rates')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete thetax rate.
     *
     * @param  \App\User  $user
     * @param  \App\TaxRate  $taxRate
     * @return mixed
     */
    public function forceDelete(User $user, TaxRate $taxRate)
    {
        return false;
    }
}
