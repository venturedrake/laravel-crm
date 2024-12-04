<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\FieldOption;

class FieldOptionObserver
{
    /**
     * Handle the fieldOption "creating" event.
     *
     * @return void
     */
    public function creating(FieldOption $fieldOption)
    {
        $fieldOption->external_id = Uuid::uuid4()->toString();
    }

    /**
     * Handle the fieldOption "created" event.
     *
     * @return void
     */
    public function created(FieldOption $fieldOption)
    {
        //
    }

    /**
     * Handle the fieldOption "updating" event.
     *
     * @return void
     */
    public function updating(FieldOption $fieldOption)
    {
        //
    }

    /**
     * Handle the fieldOption "updated" event.
     *
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
     * @return void
     */
    public function deleted(FieldOption $fieldOption)
    {
        //
    }

    /**
     * Handle the fieldOption "restored" event.
     *
     * @return void
     */
    public function restored(FieldOption $fieldOption)
    {
        //
    }

    /**
     * Handle the fieldOption "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(FieldOption $fieldOption)
    {
        //
    }
}
