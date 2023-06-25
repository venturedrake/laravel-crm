<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\LeadSource;

class LeadSourceObserver
{
    /**
     * Handle the leadSource "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\LeadSource  $leadSource
     * @return void
     */
    public function creating(LeadSource $leadSource)
    {
        $leadSource->external_id = Uuid::uuid4()->toString();
    }

    /**
     * Handle the leadSource "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\LeadSource  $leadSource
     * @return void
     */
    public function created(LeadSource $leadSource)
    {
        //
    }

    /**
     * Handle the leadSource "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\LeadSource  $leadSource
     * @return void
     */
    public function updating(LeadSource $leadSource)
    {
        //
    }

    /**
     * Handle the leadSource "updated" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\LeadSource  $leadSource
     * @return void
     */
    public function updated(LeadSource $leadSource)
    {
        //
    }

    /**
     * Handle the leadSource "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\LeadSource  $leadSource
     * @return void
     */
    public function deleting(LeadSource $leadSource)
    {
        //
    }

    /**
     * Handle the leadSource "deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\LeadSource  $leadSource
     * @return void
     */
    public function deleted(LeadSource $leadSource)
    {
        //
    }

    /**
     * Handle the leadSource "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\LeadSource  $leadSource
     * @return void
     */
    public function restored(LeadSource $leadSource)
    {
        //
    }

    /**
     * Handle the leadSource "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\LeadSource  $leadSource
     * @return void
     */
    public function forceDeleted(LeadSource $leadSource)
    {
        //
    }
}
