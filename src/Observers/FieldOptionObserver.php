<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\FieldOption;

class FieldOptionObserver
{
    /**
     * Handle the fieldOption "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\FieldOption  $fieldOption
     * @return void
     */
    public function creating(FieldOption $fieldOption)
    {
        $fieldOption->external_id = Uuid::uuid4()->toString();
    }

    /**
     * Handle the fieldOption "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\FieldOption  $fieldOption
     * @return void
     */
    public function created(FieldOption $fieldOption)
    {
        //
    }

    /**
     * Handle the fieldOption "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\FieldOption  $fieldOption
     * @return void
     */
    public function updating(FieldOption $fieldOption)
    {
        //
    }

    /**
     * Handle the fieldOption "updated" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\FieldOption  $fieldOption
     * @return void
     */
    public function updated(FieldOption $fieldOption)
    {
        //
    }

    /**
     * Handle the fieldOption "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\FieldOption  $fieldOption
     * @return void
     */
    public function deleting(FieldOption $fieldOption)
    {
        //
    }

    /**
     * Handle the fieldOption "deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\FieldOption  $fieldOption
     * @return void
     */
    public function deleted(FieldOption $fieldOption)
    {
        //
    }

    /**
     * Handle the fieldOption "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\FieldOption  $fieldOption
     * @return void
     */
    public function restored(FieldOption $fieldOption)
    {
        //
    }

    /**
     * Handle the fieldOption "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\FieldOption  $fieldOption
     * @return void
     */
    public function forceDeleted(FieldOption $fieldOption)
    {
        //
    }
}
