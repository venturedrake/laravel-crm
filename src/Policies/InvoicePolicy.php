<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\Invoice;

class InvoicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any invoices.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm invoices')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the invoice.
     *
     * @param  \App\User  $user
     * @param  \App\Invoice  $invoice
     * @return mixed
     */
    public function view(User $user, Invoice $invoice)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm invoices')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create invoices.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('create crm invoices')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the invoice.
     *
     * @param  \App\User  $user
     * @param  \App\Invoice  $invoice
     * @return mixed
     */
    public function update(User $user, Invoice $invoice)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('edit crm invoices')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the invoice.
     *
     * @param  \App\User  $user
     * @param  \App\Invoice  $invoice
     * @return mixed
     */
    public function delete(User $user, Invoice $invoice)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('delete crm invoices')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the invoice.
     *
     * @param  \App\User  $user
     * @param  \App\Invoice  $invoice
     * @return mixed
     */
    public function restore(User $user, Invoice $invoice)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('delete crm invoices')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the invoice.
     *
     * @param  \App\User  $user
     * @param  \App\Invoice  $invoice
     * @return mixed
     */
    public function forceDelete(User $user, Invoice $invoice)
    {
        return false;
    }

    protected function isEnabled()
    {
        if(is_array(config('laravel-crm.modules')) && in_array('invoices', config('laravel-crm.modules'))) {
            return true;
        } elseif(! config('laravel-crm.modules')) {
            return true;
        }
    }
}
