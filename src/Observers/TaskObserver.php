<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Task;

class TaskObserver
{
    /**
     * Handle the task "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Task  $task
     * @return void
     */
    public function creating(Task $task)
    {
        $task->external_id = Uuid::uuid4()->toString();

        if (! app()->runningInConsole()) {
            $task->user_created_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the task "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Task  $task
     * @return void
     */
    public function created(Task $task)
    {
        //
    }

    /**
     * Handle the task "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Task  $task
     * @return void
     */
    public function updating(Task $task)
    {
        if (! app()->runningInConsole()) {
            $task->user_updated_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the task "updated" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Task  $task
     * @return void
     */
    public function updated(Task $task)
    {
        //
    }

    /**
     * Handle the task "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\Task  $task
     * @return void
     */
    public function deleting(Task $task)
    {
        if (! app()->runningInConsole()) {
            $task->user_deleted_id = auth()->user()->id ?? null;
            $task->saveQuietly();

            if ($task->activity) {
                $task->activity->delete();
            }
        }
    }

    /**
     * Handle the task "deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Task  $task
     * @return void
     */
    public function deleted(Task $task)
    {
        //
    }

    /**
     * Handle the task "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Task  $task
     * @return void
     */
    public function restored(Task $task)
    {
        if (! app()->runningInConsole()) {
            $task->user_deleted_id = auth()->user()->id ?? null;
            $task->saveQuietly();
        }
    }

    /**
     * Handle the task "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Task  $task
     * @return void
     */
    public function forceDeleted(Task $task)
    {
        //
    }
}
