<?php

namespace VentureDrake\LaravelCrm\Observers;

use VentureDrake\LaravelCrm\Organisation;

class OrganisationObserver
{

    /**
     * Handle the organisation "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Organisation  $organisation
     * @return void
     */
    public function creating(Organisation $organisation)
    {
        if (! app()->runningInConsole()) {
            $organisation->user_created_id = auth()->user()->id ?? null;
        }
    }
    
    /**
     * Handle the organisation "created" event.
     *
     * @param  \ VentureDrake\LaravelCrm\Organisation  $organisation
     * @return void
     */
    public function created(Organisation $organisation)
    {
        //
    }

    /**
     * Handle the organisation "updated" event.
     *
     * @param  \ VentureDrake\LaravelCrm\Organisation  $organisation
     * @return void
     */
    public function updated(Organisation $organisation)
    {
        //
    }

    /**
     * Handle the organisation "deleted" event.
     *
     * @param  \ VentureDrake\LaravelCrm\Organisation  $organisation
     * @return void
     */
    public function deleted(Organisation $organisation)
    {
        //
    }

    /**
     * Handle the organisation "restored" event.
     *
     * @param  \ VentureDrake\LaravelCrm\Organisation  $organisation
     * @return void
     */
    public function restored(Organisation $organisation)
    {
        //
    }

    /**
     * Handle the organisation "force deleted" event.
     *
     * @param  \ VentureDrake\LaravelCrm\Organisation  $organisation
     * @return void
     */
    public function forceDeleted(Organisation $organisation)
    {
        //
    }
}
