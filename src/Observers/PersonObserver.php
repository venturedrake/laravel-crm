<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\PositionService;
use VentureDrake\LaravelCrm\Support\Modules;

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
        $person->external_id = Uuid::uuid4()->toString();

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
        // Leaving an organisation ends the positions held there.
        if (Modules::enabled('positions') && $person->wasChanged('organization_id') && $person->getOriginal('organization_id')) {
            app(PositionService::class)->endForPerson($person->id, (int) $person->getOriginal('organization_id'));
        }
    }

    /**
     * Handle the person "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\Person  $person
     * @return void
     */
    public function deleting(Person $person)
    {
        if (! app()->runningInConsole()) {
            $person->user_deleted_id = auth()->user()->id ?? null;
            $person->saveQuietly();
        }
    }

    /**
     * Handle the person "deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Person  $person
     * @return void
     */
    public function deleted(Person $person)
    {
        // A deleted person's positions go with them.
        if (Modules::enabled('positions')) {
            app(PositionService::class)->deleteFor($person);
        }
    }

    /**
     * Handle the person "restoring" event.
     *
     * @param  \VentureDrake\LaravelCrm\Person  $person
     * @return void
     */
    public function restoring(Person $person) {}

    /**
     * Handle the person "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Person  $person
     * @return void
     */
    public function restored(Person $person)
    {
        if (! app()->runningInConsole()) {
            $person->user_deleted_id = null;
            $person->saveQuietly();
        }
    }

    /**
     * Handle the person "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Person  $person
     * @return void
     */
    public function forceDeleted(Person $person)
    {
        if (Modules::enabled('positions')) {
            app(PositionService::class)->deleteFor($person, true);
        }
    }
}
