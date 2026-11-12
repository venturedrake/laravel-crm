<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Position;

class PositionObserver
{
    /**
     * Handle the position "creating" event.
     *
     * @return void
     */
    public function creating(Position $position)
    {
        $position->external_id = Uuid::uuid4()->toString();

        if (! $position->start_date) {
            $position->start_date = now()->toDateString();
        }

        if (! app()->runningInConsole()) {
            $position->user_created_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the position "updating" event.
     *
     * @return void
     */
    public function updating(Position $position)
    {
        if (! app()->runningInConsole()) {
            $position->user_updated_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the position "deleting" event.
     *
     * @return void
     */
    public function deleting(Position $position)
    {
        if (! app()->runningInConsole()) {
            $position->user_deleted_id = auth()->user()->id ?? null;
            $position->saveQuietly();
        }
    }

    /**
     * Handle the position "restored" event.
     *
     * @return void
     */
    public function restored(Position $position)
    {
        if (! app()->runningInConsole()) {
            $position->user_restored_id = auth()->user()->id ?? null;
            $position->saveQuietly();
        }
    }
}
