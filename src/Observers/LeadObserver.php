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
     * Handle the lead "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Lead  $lead
     * @return void
     */
    public function updating(Lead $lead)
    {
        if (! app()->runningInConsole()) {
            $lead->user_updated_id = auth()->user()->id ?? null;
        }
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
     * Handle the lead "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\Lead  $lead
     * @return void
     */
    public function deleting(Lead $lead)
    {
        if (! app()->runningInConsole()) {
            $lead->user_deleted_id = auth()->user()->id ?? null;
            $lead->saveQuietly();
        }
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
        if (! app()->runningInConsole()) {
            $lead->user_deleted_id = auth()->user()->id ?? null;
            $lead->saveQuietly();
        }
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
