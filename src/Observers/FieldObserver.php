<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Field;

class FieldObserver
{
    /**
     * Handle the field "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Field  $field
     * @return void
     */
    public function creating(Field $field)
    {
        $field->external_id = Uuid::uuid4()->toString();
    }

    /**
     * Handle the field "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Field  $field
     * @return void
     */
    public function created(Field $field)
    {
        //
    }

    /**
     * Handle the field "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Field  $field
     * @return void
     */
    public function updating(Field $field)
    {
        //
    }

    /**
     * Handle the field "updated" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Field  $field
     * @return void
     */
    public function updated(Field $field)
    {
        //
    }

    /**
     * Handle the field "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\Field  $field
     * @return void
     */
    public function deleting(Field $field)
    {
        //
    }

    /**
     * Handle the field "deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Field  $field
     * @return void
     */
    public function deleted(Field $field)
    {
        //
    }

    /**
     * Handle the field "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Field  $field
     * @return void
     */
    public function restored(Field $field)
    {
        //
    }

    /**
     * Handle the field "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Field  $field
     * @return void
     */
    public function forceDeleted(Field $field)
    {
        //
    }
}
