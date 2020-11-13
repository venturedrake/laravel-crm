<?php

namespace VentureDrake\LaravelCrm\Observers;

use VentureDrake\LaravelCrm\Models\Lead;

class LeadObserver
{
    /**
     * Handle the lead "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Lead  $lead
     * @return void
     */
    public function creating(Lead $lead)
    {
        if (! app()->runningInConsole()) {
            $lead->user_created_id = auth()->user()->id ?? null;
        }
    }
    
    /**
     * Handle the lead "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Lead  $lead
     * @return void
     */
    public function created(Lead $lead)
    {
        //
    }

    /**
     * Handle the lead "updated" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Lead  $lead
     * @return void
     */
    public function updated(Lead $lead)
    {
        //
    }

    /**
     * Handle the lead "deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Lead  $lead
     * @return void
     */
    public function deleted(Lead $lead)
    {
        //
    }

    /**
     * Handle the lead "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Lead  $lead
     * @return void
     */
    public function restored(Lead $lead)
    {
        //
    }

    /**
     * Handle the lead "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Lead  $lead
     * @return void
     */
    public function forceDeleted(Lead $lead)
    {
        //
    }
}
