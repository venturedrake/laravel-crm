<?php

namespace VentureDrake\LaravelCrm\Observers;

use VentureDrake\LaravelCrm\Models\Phone;

class PhoneObserver
{
    /**
     * Handle the phone "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Phone  $phone
     * @return void
     */
    public function creating(Phone $phone)
    {
        if (! app()->runningInConsole()) {
            $phone->user_created_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the phone "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Phone  $phone
     * @return void
     */
    public function created(Phone $phone)
    {
        //
    }

    /**
     * Handle the phone "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Phone  $phone
     * @return void
     */
    public function updating(Phone $phone)
    {
        if (! app()->runningInConsole()) {
            $phone->user_updated_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the phone "updated" event.
     *
     * @param  \VentureDrake\LaravelCrm\Phone  $phone
     * @return void
     */
    public function updated(Phone $phone)
    {
        //
    }

    /**
     * Handle the phone "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\Phone  $phone
     * @return void
     */
    public function deleting(Phone $phone)
    {
        if (! app()->runningInConsole()) {
            $phone->user_deleted_id = auth()->user()->id ?? null;
            $phone->saveQuietly();
        }
    }

    /**
     * Handle the phone "deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Phone  $phone
     * @return void
     */
    public function deleted(Phone $phone)
    {
        //
    }

    /**
     * Handle the phone "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Phone  $phone
     * @return void
     */
    public function restored(Phone $phone)
    {
        if (! app()->runningInConsole()) {
            $phone->user_restored_id = auth()->user()->id ?? null;
            $phone->saveQuietly();
        }
    }

    /**
     * Handle the phone "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Phone  $phone
     * @return void
     */
    public function forceDeleted(Phone $phone)
    {
        //
    }
}
