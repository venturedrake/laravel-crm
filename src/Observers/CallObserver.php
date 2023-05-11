<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Call;

class CallObserver
{
    /**
     * Handle the call "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Call  $call
     * @return void
     */
    public function creating(Call $call)
    {
        $call->external_id = Uuid::uuid4()->toString();

        if (! app()->runningInConsole()) {
            $call->user_created_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the call "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Call  $call
     * @return void
     */
    public function created(Call $call)
    {
        //
    }

    /**
     * Handle the call "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Call  $call
     * @return void
     */
    public function updating(Call $call)
    {
        if (! app()->runningInConsole()) {
            $call->user_updated_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the call "updated" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Call  $call
     * @return void
     */
    public function updated(Call $call)
    {
        //
    }

    /**
     * Handle the call "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\Call  $call
     * @return void
     */
    public function deleting(Call $call)
    {
        if (! app()->runningInConsole()) {
            $call->user_deleted_id = auth()->user()->id ?? null;
            $call->saveQuietly();

            if ($call->activity) {
                $call->activity->delete();
            }
        }
    }

    /**
     * Handle the call "deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Call  $call
     * @return void
     */
    public function deleted(Call $call)
    {
        //
    }

    /**
     * Handle the call "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Call  $call
     * @return void
     */
    public function restored(Call $call)
    {
        if (! app()->runningInConsole()) {
            $call->user_deleted_id = auth()->user()->id ?? null;
            $call->saveQuietly();
        }
    }

    /**
     * Handle the call "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Call  $call
     * @return void
     */
    public function forceDeleted(Call $call)
    {
        //
    }
}
