<?php

namespace VentureDrake\LaravelCrm\Observers;

use VentureDrake\LaravelCrm\Models\Person;

class PersonObserver
{
    /**
     * Handle the person "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Person  $person
     * @return void
     */
    public function creating(Person $person)
    {
        if (! app()->runningInConsole()) {
            $person->user_created_id = auth()->user()->id ?? null;
        }
    }
    
    /**
     * Handle the person "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Person  $person
     * @return void
     */
    public function created(Person $person)
    {
        //
    }

    /**
     * Handle the person "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Person  $person
     * @return void
     */
    public function updating(Person $person)
    {
        if (! app()->runningInConsole()) {
            $person->user_updated_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the person "updated" event.
     *
     * @param  \VentureDrake\LaravelCrm\Person  $person
     * @return void
     */
    public function updated(Person $person)
    {
        //
    }

    /**
     * Handle the person "deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Person  $person
     * @return void
     */
    public function deleted(Person $person)
    {
        //
    }

    /**
     * Handle the person "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Person  $person
     * @return void
     */
    public function restored(Person $person)
    {
        //
    }

    /**
     * Handle the person "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Person  $person
     * @return void
     */
    public function forceDeleted(Person $person)
    {
        //
    }
}
