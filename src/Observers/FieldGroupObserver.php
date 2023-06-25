<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\FieldGroup;

class FieldGroupObserver
{
    /**
     * Handle the fieldGroup "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\FieldGroup  $fieldGroup
     * @return void
     */
    public function creating(FieldGroup $fieldGroup)
    {
        $fieldGroup->external_id = Uuid::uuid4()->toString();
    }

    /**
     * Handle the fieldGroup "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\FieldGroup  $fieldGroup
     * @return void
     */
    public function created(FieldGroup $fieldGroup)
    {
        //
    }

    /**
     * Handle the fieldGroup "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\FieldGroup  $fieldGroup
     * @return void
     */
    public function updating(FieldGroup $fieldGroup)
    {
        //
    }

    /**
     * Handle the fieldGroup "updated" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\FieldGroup  $fieldGroup
     * @return void
     */
    public function updated(FieldGroup $fieldGroup)
    {
        //
    }

    /**
     * Handle the fieldGroup "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\FieldGroup  $fieldGroup
     * @return void
     */
    public function deleting(FieldGroup $fieldGroup)
    {
        //
    }

    /**
     * Handle the fieldGroup "deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\FieldGroup  $fieldGroup
     * @return void
     */
    public function deleted(FieldGroup $fieldGroup)
    {
        //
    }

    /**
     * Handle the fieldGroup "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\FieldGroup  $fieldGroup
     * @return void
     */
    public function restored(FieldGroup $fieldGroup)
    {
        //
    }

    /**
     * Handle the fieldGroup "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\FieldGroup  $fieldGroup
     * @return void
     */
    public function forceDeleted(FieldGroup $fieldGroup)
    {
        //
    }
}
