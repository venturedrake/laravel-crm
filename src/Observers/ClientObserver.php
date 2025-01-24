<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Customer;

class ClientObserver
{
    /**
     * Handle the client "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Client  $client
     * @return void
     */
    public function creating(Customer $client)
    {
        $client->external_id = Uuid::uuid4()->toString();

        if (! app()->runningInConsole()) {
            $client->user_created_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the client "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Client  $client
     * @return void
     */
    public function created(Customer $client)
    {
        //
    }

    /**
     * Handle the client "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Client  $client
     * @return void
     */
    public function updating(Customer $client)
    {
        if (! app()->runningInConsole()) {
            $client->user_updated_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the client "updated" event.
     *
     * @param  \VentureDrake\LaravelCrm\Client  $client
     * @return void
     */
    public function updated(Customer $client)
    {
        //
    }

    /**
     * Handle the client "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\Client  $client
     * @return void
     */
    public function deleting(Customer $client)
    {
        if (! app()->runningInConsole()) {
            $client->user_deleted_id = auth()->user()->id ?? null;
            $client->saveQuietly();
        }
    }

    /**
     * Handle the client "deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Client  $client
     * @return void
     */
    public function deleted(Customer $client)
    {
        //
    }

    /**
     * Handle the client "restoring" event.
     *
     * @param  \VentureDrake\LaravelCrm\Client  $client
     * @return void
     */
    public function restoring(Customer $client) {}

    /**
     * Handle the client "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Client  $client
     * @return void
     */
    public function restored(Customer $client)
    {
        if (! app()->runningInConsole()) {
            $client->user_restored_id = auth()->user()->id ?? null;
            $client->saveQuietly();
        }
    }

    /**
     * Handle the client "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Client  $client
     * @return void
     */
    public function forceDeleted(Customer $client)
    {
        //
    }
}
