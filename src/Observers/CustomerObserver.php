<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Customer;

class CustomerObserver
{
    /**
     * Handle the customer "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Customer  $customer
     * @return void
     */
    public function creating(Customer $customer)
    {
        $customer->external_id = Uuid::uuid4()->toString();

        if (! app()->runningInConsole()) {
            $customer->user_created_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the customer "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Customer  $customer
     * @return void
     */
    public function created(Customer $customer)
    {
        //
    }

    /**
     * Handle the customer "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Customer  $customer
     * @return void
     */
    public function updating(Customer $customer)
    {
        if (! app()->runningInConsole()) {
            $customer->user_updated_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the customer "updated" event.
     *
     * @param  \VentureDrake\LaravelCrm\Customer  $customer
     * @return void
     */
    public function updated(Customer $customer)
    {
        //
    }

    /**
     * Handle the customer "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\Customer  $customer
     * @return void
     */
    public function deleting(Customer $customer)
    {
        if (! app()->runningInConsole()) {
            $customer->user_deleted_id = auth()->user()->id ?? null;
            $customer->saveQuietly();
        }
    }

    /**
     * Handle the customer "deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Customer  $customer
     * @return void
     */
    public function deleted(Customer $customer)
    {
        //
    }

    /**
     * Handle the customer "restoring" event.
     *
     * @param  \VentureDrake\LaravelCrm\Customer  $customer
     * @return void
     */
    public function restoring(Customer $customer) {}

    /**
     * Handle the customer "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Customer  $customer
     * @return void
     */
    public function restored(Customer $customer)
    {
        if (! app()->runningInConsole()) {
            $customer->user_restored_id = auth()->user()->id ?? null;
            $customer->saveQuietly();
        }
    }

    /**
     * Handle the customer "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Customer  $customer
     * @return void
     */
    public function forceDeleted(Customer $customer)
    {
        //
    }
}
