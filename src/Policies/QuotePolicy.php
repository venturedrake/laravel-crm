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
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm quotes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the quote.
     *
     * @param  \App\Quote  $quote
     * @return mixed
     */
    public function view(User $user, Quote $quote)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm quotes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create quotes.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('create crm quotes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the quote.
     *
     * @param  \App\Quote  $quote
     * @return mixed
     */
    public function update(User $user, Quote $quote)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('edit crm quotes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can edit the quote's contents.
     *
     * Narrower than update(): accepting, rejecting and moving a quote on the
     * board still go through update() once it is accepted or ordered, but
     * its lines and totals are fixed then.
     *
     * @return mixed
     */
    public function edit(User $user, Quote $quote)
    {
        if ($this->update($user, $quote) && ! $quote->isLocked()) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the quote.
     *
     * A quote with an order cannot be deleted: the order would lose its
     * link back to the quote it came from.
     *
     * @param  \App\Quote  $quote
     * @return mixed
     */
    public function delete(User $user, Quote $quote)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('delete crm quotes')) {
            if (! $quote->canBeDeleted()) {
                return $this->deny($quote->deleteBlockedReason());
            }

            return true;
        }
    }

    /**
     * Determine whether the user can restore the quote.
     *
     * @param  \App\Quote  $quote
     * @return mixed
     */
    public function restore(User $user, Quote $quote)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('delete crm quotes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the quote.
     *
     * @param  \App\Quote  $quote
     * @return mixed
     */
    public function forceDelete(User $user, Quote $quote)
    {
        return false;
    }

    /**
     * Determine whether the user can manage the products (line items) on quotes.
     *
     * Gated on the quote permission rather than the product permission: line-item
     * editing is part of building a quote, and neither Manager nor Employee holds
     * any 'crm products' permission.
     *
     * @return mixed
     */
    public function manageProducts(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('edit crm quotes')) {
            return true;
        }
    }

    protected function isEnabled()
    {
        if (is_array(config('laravel-crm.modules')) && in_array('quotes', config('laravel-crm.modules'))) {
            return true;
        } elseif (! config('laravel-crm.modules')) {
            return true;
        }
    }
}
