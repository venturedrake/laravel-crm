<?php

namespace VentureDrake\LaravelCrm\Observers;

use VentureDrake\LaravelCrm\Models\Note;

class NoteObserver
{
    /**
     * Handle the note "creating" event.
     *
     * @return void
     */
    public function creating(Note $note)
    {
        if (! app()->runningInConsole()) {
            $note->user_created_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the note "created" event.
     *
     * @return void
     */
    public function created(Note $note)
    {
        //
    }

    /**
     * Handle the note "updating" event.
     *
     * @return void
     */
    public function updating(Note $note)
    {
        if (! app()->runningInConsole()) {
            $note->user_updated_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the note "updated" event.
     *
     * @return void
     */
    public function updated(Note $note)
    {
        //
    }

    /**
     * Handle the note "deleting" event.
     *
     * @return void
     */
    public function deleting(Note $note)
    {
        if (! app()->runningInConsole()) {
            $note->user_deleted_id = auth()->user()->id ?? null;
            $note->saveQuietly();

            if ($note->activity) {
                $note->activity->delete();
            }
        }
    }

    /**
     * Handle the note "deleted" event.
     *
     * @return void
     */
    public function deleted(Note $note)
    {
        //
    }

    /**
     * Handle the note "restoring" event.
     *
     * @return void
     */
    public function restoring(Note $note) {}

    /**
     * Handle the note "restored" event.
     *
     * @return void
     */
    public function restored(Note $note)
    {
        if (! app()->runningInConsole()) {
            $note->user_restored_id = auth()->user()->id ?? null;
            $note->saveQuietly();
        }
    }

    /**
     * Handle the note "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(Note $note)
    {
        //
    }
}
