<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Organization;

class OrganizationObserver
{
    /**
     * Handle the organization "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Organization  $organization
     * @return void
     */
    public function creating(Organization $organization)
    {
        $organization->external_id = Uuid::uuid4()->toString();

        if (! app()->runningInConsole()) {
            $organization->user_created_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the organization "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Organization  $organization
     * @return void
     */
    public function created(Organization $organization)
    {
        //
    }

    /**
     * Handle the organization "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Organization  $organization
     * @return void
     */
    public function updating(Organization $organization)
    {
        if (! app()->runningInConsole()) {
            $organization->user_updated_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the organization "updated" event.
     *
     * @param  \ VentureDrake\LaravelCrm\Organization  $organization
     * @return void
     */
    public function updated(Organization $organization)
    {
        //
    }

    /**
     * Handle the organization "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\Organization  $organization
     * @return void
     */
    public function deleting(Organization $organization)
    {
        if (! app()->runningInConsole()) {
            $organization->user_deleted_id = auth()->user()->id ?? null;
            $organization->saveQuietly();
        }
    }

    /**
     * Handle the organization "deleted" event.
     *
     * @param  \ VentureDrake\LaravelCrm\Organization  $organization
     * @return void
     */
    public function deleted(Organization $organization)
    {
        //
    }

    /**
     * Handle the organization "restored" event.
     *
     * @param  \ VentureDrake\LaravelCrm\Organization  $organization
     * @return void
     */
    public function restored(Organization $organization)
    {
        if (! app()->runningInConsole()) {
            $organization->user_deleted_id = auth()->user()->id ?? null;
            $organization->saveQuietly();
        }
    }

    /**
     * Handle the organization "force deleted" event.
     *
     * @param  \ VentureDrake\LaravelCrm\Organization  $organization
     * @return void
     */
    public function forceDeleted(Organization $organization)
    {
        //
    }
}
