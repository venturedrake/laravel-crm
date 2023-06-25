<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Organisation;

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
        $organisation->external_id = Uuid::uuid4()->toString();

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
     * Handle the organisation "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Organisation  $organisation
     * @return void
     */
    public function updating(Organisation $organisation)
    {
        if (! app()->runningInConsole()) {
            $organisation->user_updated_id = auth()->user()->id ?? null;
        }
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
     * Handle the organisation "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\Organisation  $organisation
     * @return void
     */
    public function deleting(Organisation $organisation)
    {
        if (! app()->runningInConsole()) {
            $organisation->user_deleted_id = auth()->user()->id ?? null;
            $organisation->saveQuietly();
        }
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
        if (! app()->runningInConsole()) {
            $organisation->user_deleted_id = auth()->user()->id ?? null;
            $organisation->saveQuietly();
        }
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
