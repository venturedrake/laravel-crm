<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Activity;

class ActivityObserver
{
    /**
     * Handle the activity "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Activity  $activity
     * @return void
     */
    public function creating(Activity $activity)
    {
        $activity->external_id = Uuid::uuid4()->toString();
    }

    /**
     * Handle the activity "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Activity  $activity
     * @return void
     */
    public function created(Activity $activity)
    {
        //
    }

    /**
     * Handle the activity "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Activity  $activity
     * @return void
     */
    public function updating(Activity $activity)
    {
        //
    }

    /**
     * Handle the activity "updated" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Activity  $activity
     * @return void
     */
    public function updated(Activity $activity)
    {
        //
    }

    /**
     * Handle the activity "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\Activity  $activity
     * @return void
     */
    public function deleting(Activity $activity)
    {
        //
    }

    /**
     * Handle the activity "deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Activity  $activity
     * @return void
     */
    public function deleted(Activity $activity)
    {
        //
    }

    /**
     * Handle the activity "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Activity  $activity
     * @return void
     */
    public function restored(Activity $activity)
    {
        //
    }

    /**
     * Handle the activity "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Activity  $activity
     * @return void
     */
    public function forceDeleted(Activity $activity)
    {
        //
    }
}
