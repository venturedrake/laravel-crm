<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Field;

class FieldObserver
{
    /**
     * Handle the field "creating" event.
     *
     * @return void
     */
    public function creating(Field $field)
    {
        $field->external_id = Uuid::uuid4()->toString();
    }

    /**
     * Handle the field "created" event.
     *
     * @return void
     */
    public function created(Field $field)
    {
        //
    }

    /**
     * Handle the field "updating" event.
     *
     * @return void
     */
    public function updating(Field $field)
    {
        //
    }

    /**
     * Handle the field "updated" event.
     *
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
     * @return void
     */
    public function deleted(Field $field)
    {
        //
    }

    /**
     * Handle the field "restored" event.
     *
     * @return void
     */
    public function restored(Field $field)
    {
        //
    }

    /**
     * Handle the field "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(Field $field)
    {
        //
    }
}
