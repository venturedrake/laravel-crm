<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\XeroPerson;

class XeroPersonObserver
{
    /**
     * Handle the xeroPerson "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\XeroPerson  $xeroPerson
     * @return void
     */
    public function creating(XeroPerson $xeroPerson)
    {
        $xeroPerson->external_id = Uuid::uuid4()->toString();
    }

    /**
     * Handle the xeroPerson "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\XeroPerson  $xeroPerson
     * @return void
     */
    public function created(XeroPerson $xeroPerson)
    {
        //
    }

    /**
     * Handle the xeroPerson "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\XeroPerson  $xeroPerson
     * @return void
     */
    public function updating(XeroPerson $xeroPerson)
    {
        //
    }

    /**
     * Handle the xeroPerson "updated" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\XeroPerson  $xeroPerson
     * @return void
     */
    public function updated(XeroPerson $xeroPerson)
    {
        //
    }

    /**
     * Handle the xeroPerson "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\XeroPerson  $xeroPerson
     * @return void
     */
    public function deleting(XeroPerson $xeroPerson)
    {
        //
    }

    /**
     * Handle the xeroPerson "deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\XeroPerson  $xeroPerson
     * @return void
     */
    public function deleted(XeroPerson $xeroPerson)
    {
        //
    }

    /**
     * Handle the xeroPerson "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\XeroPerson  $xeroPerson
     * @return void
     */
    public function restored(XeroPerson $xeroPerson)
    {
        //
    }

    /**
     * Handle the xeroPerson "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\XeroPerson  $xeroPerson
     * @return void
     */
    public function forceDeleted(XeroPerson $xeroPerson)
    {
        //
    }
}
