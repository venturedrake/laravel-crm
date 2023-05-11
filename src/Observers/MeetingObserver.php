<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Meeting;

class MeetingObserver
{
    /**
     * Handle the meeting "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Meeting  $meeting
     * @return void
     */
    public function creating(Meeting $meeting)
    {
        $meeting->external_id = Uuid::uuid4()->toString();

        if (! app()->runningInConsole()) {
            $meeting->user_created_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the meeting "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Meeting  $meeting
     * @return void
     */
    public function created(Meeting $meeting)
    {
        //
    }

    /**
     * Handle the meeting "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Meeting  $meeting
     * @return void
     */
    public function updating(Meeting $meeting)
    {
        if (! app()->runningInConsole()) {
            $meeting->user_updated_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the meeting "updated" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Meeting  $meeting
     * @return void
     */
    public function updated(Meeting $meeting)
    {
        //
    }

    /**
     * Handle the meeting "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\Meeting  $meeting
     * @return void
     */
    public function deleting(Meeting $meeting)
    {
        if (! app()->runningInConsole()) {
            $meeting->user_deleted_id = auth()->user()->id ?? null;
            $meeting->saveQuietly();

            if ($meeting->activity) {
                $meeting->activity->delete();
            }
        }
    }

    /**
     * Handle the meeting "deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Meeting  $meeting
     * @return void
     */
    public function deleted(Meeting $meeting)
    {
        //
    }

    /**
     * Handle the meeting "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Meeting  $meeting
     * @return void
     */
    public function restored(Meeting $meeting)
    {
        if (! app()->runningInConsole()) {
            $meeting->user_deleted_id = auth()->user()->id ?? null;
            $meeting->saveQuietly();
        }
    }

    /**
     * Handle the meeting "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Meeting  $meeting
     * @return void
     */
    public function forceDeleted(Meeting $meeting)
    {
        //
    }
}
