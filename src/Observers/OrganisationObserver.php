<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Organization;

class OrganisationObserver
{
    /**
     * Handle the organisation "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Organisation  $organisation
     * @return void
     */
    public function creating(Organization $organisation)
    {
        $organisation->external_id = Uuid::uuid4()->toString();

        if (! app()->runningInConsole()) {
            $organisation->user_created_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the organisation "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Organisation  $organisation
     * @return void
     */
    public function created(Organization $organisation)
    {
        //
    }

    /**
     * Handle the organisation "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Organisation  $organisation
     * @return void
     */
    public function updating(Organization $organisation)
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
    public function updated(Organization $organisation)
    {
        //
    }

    /**
     * Handle the organisation "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\Organisation  $organisation
     * @return void
     */
    public function deleting(Organization $organisation)
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
    public function deleted(Organization $organisation)
    {
        //
    }

    /**
     * Handle the organisation "restored" event.
     *
     * @param  \ VentureDrake\LaravelCrm\Organisation  $organisation
     * @return void
     */
    public function restored(Organization $organisation)
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
    public function forceDeleted(Organization $organisation)
    {
        //
    }
}
