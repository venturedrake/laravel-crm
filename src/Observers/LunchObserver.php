<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Lunch;

class LunchObserver
{
    /**
     * Handle the lunch "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Lunch  $lunch
     * @return void
     */
    public function creating(Lunch $lunch)
    {
        $lunch->external_id = Uuid::uuid4()->toString();

        if (! app()->runningInConsole()) {
            $lunch->user_created_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the lunch "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Lunch  $lunch
     * @return void
     */
    public function created(Lunch $lunch)
    {
        //
    }

    /**
     * Handle the lunch "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Lunch  $lunch
     * @return void
     */
    public function updating(Lunch $lunch)
    {
        if (! app()->runningInConsole()) {
            $lunch->user_updated_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the lunch "updated" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Lunch  $lunch
     * @return void
     */
    public function updated(Lunch $lunch)
    {
        //
    }

    /**
     * Handle the lunch "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\Lunch  $lunch
     * @return void
     */
    public function deleting(Lunch $lunch)
    {
        if (! app()->runningInConsole()) {
            $lunch->user_deleted_id = auth()->user()->id ?? null;
            $lunch->saveQuietly();

            if ($lunch->activity) {
                $lunch->activity->delete();
            }
        }
    }

    /**
     * Handle the lunch "deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Lunch  $lunch
     * @return void
     */
    public function deleted(Lunch $lunch)
    {
        //
    }

    /**
     * Handle the lunch "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Lunch  $lunch
     * @return void
     */
    public function restored(Lunch $lunch)
    {
        if (! app()->runningInConsole()) {
            $lunch->user_deleted_id = auth()->user()->id ?? null;
            $lunch->saveQuietly();
        }
    }

    /**
     * Handle the lunch "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Lunch  $lunch
     * @return void
     */
    public function forceDeleted(Lunch $lunch)
    {
        //
    }
}
