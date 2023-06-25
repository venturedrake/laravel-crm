<?php

namespace VentureDrake\LaravelCrm\Observers;

use VentureDrake\LaravelCrm\Models\Deal;

class DealObserver
{
    /**
     * Handle the deal "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Deal  $deal
     * @return void
     */
    public function creating(Deal $deal)
    {
        if (! app()->runningInConsole()) {
            $deal->user_created_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the deal "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Deal  $deal
     * @return void
     */
    public function created(Deal $deal)
    {
        //
    }

    /**
     * Handle the deal "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Deal  $deal
     * @return void
     */
    public function updating(Deal $deal)
    {
        if (! app()->runningInConsole()) {
            $deal->user_updated_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the deal "updated" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Deal  $deal
     * @return void
     */
    public function updated(Deal $deal)
    {
        //
    }

    /**
     * Handle the deal "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\Deal  $deal
     * @return void
     */
    public function deleting(Deal $deal)
    {
        if (! app()->runningInConsole()) {
            $deal->user_deleted_id = auth()->user()->id ?? null;
            $deal->saveQuietly();
        }
    }

    /**
     * Handle the deal "deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Deal  $deal
     * @return void
     */
    public function deleted(Deal $deal)
    {
        //
    }

    /**
     * Handle the deal "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Deal  $deal
     * @return void
     */
    public function restored(Deal $deal)
    {
        if (! app()->runningInConsole()) {
            $deal->user_deleted_id = auth()->user()->id ?? null;
            $deal->saveQuietly();
        }
    }

    /**
     * Handle the deal "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Deal  $deal
     * @return void
     */
    public function forceDeleted(Deal $deal)
    {
        //
    }
}
