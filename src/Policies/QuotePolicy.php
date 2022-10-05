<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\Quote;

class QuotePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any quotes.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasPermissionTo('view crm quotes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the quote.
     *
     * @param  \App\User  $user
     * @param  \App\Quote  $quote
     * @return mixed
     */
    public function view(User $user, Quote $quote)
    {
        if ($user->hasPermissionTo('view crm quotes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create quotes.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasPermissionTo('create crm quotes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the quote.
     *
     * @param  \App\User  $user
     * @param  \App\Quote  $quote
     * @return mixed
     */
    public function update(User $user, Quote $quote)
    {
        if ($user->hasPermissionTo('edit crm quotes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the quote.
     *
     * @param  \App\User  $user
     * @param  \App\Quote  $quote
     * @return mixed
     */
    public function delete(User $user, Quote $quote)
    {
        if ($user->hasPermissionTo('delete crm quotes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the quote.
     *
     * @param  \App\User  $user
     * @param  \App\Quote  $quote
     * @return mixed
     */
    public function restore(User $user, Quote $quote)
    {
        if ($user->hasPermissionTo('delete crm quotes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the quote.
     *
     * @param  \App\User  $user
     * @param  \App\Quote  $quote
     * @return mixed
     */
    public function forceDelete(User $user, Quote $quote)
    {
        return false;
    }
}
