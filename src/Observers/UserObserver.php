<?php

namespace VentureDrake\LaravelCrm\Observers;

use App\User;
use VentureDrake\LaravelCrm\Support\UserReferences;

class UserObserver
{
    /**
     * Handle the user "creating" event.
     *
     * @return void
     */
    public function creating(User $user)
    {
        //
    }

    /**
     * Handle the user "created" event.
     *
     * @return void
     */
    public function created(User $user)
    {
        //
    }

    /**
     * Handle the user "updating" event.
     *
     * @return void
     */
    public function updating(User $user)
    {
        //
    }

    /**
     * Handle the user "updated" event.
     *
     * @return void
     */
    public function updated(User $user)
    {
        //
    }

    /**
     * Handle the user "deleting" event.
     *
     * @return void
     */
    public function deleting(User $user)
    {
        // A soft delete keeps the row, so its foreign keys still resolve.
        if (method_exists($user, 'isForceDeleting') && ! $user->isForceDeleting()) {
            return;
        }

        UserReferences::release($user);
    }

    /**
     * Handle the user "deleted" event.
     *
     * @return void
     */
    public function deleted(User $user)
    {
        //
    }

    /**
     * Handle the user "restored" event.
     *
     * @return void
     */
    public function restored(User $user)
    {
        //
    }

    /**
     * Handle the user "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(User $user)
    {
        //
    }
}
