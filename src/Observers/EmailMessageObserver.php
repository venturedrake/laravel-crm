<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\EmailMessage;

class EmailMessageObserver
{
    /**
     * Handle the email message "creating" event.
     *
     * @return void
     */
    public function creating(EmailMessage $emailMessage)
    {
        $emailMessage->external_id = Uuid::uuid4()->toString();

        if (! app()->runningInConsole()) {
            $emailMessage->user_created_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the email message "updating" event.
     *
     * @return void
     */
    public function updating(EmailMessage $emailMessage)
    {
        if (! app()->runningInConsole()) {
            $emailMessage->user_updated_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the email message "deleting" event.
     *
     * A deleted email leaves the timeline of every record it was shown on.
     *
     * @return void
     */
    public function deleting(EmailMessage $emailMessage)
    {
        if (! app()->runningInConsole()) {
            $emailMessage->user_deleted_id = auth()->user()->id ?? null;
            $emailMessage->saveQuietly();
        }

        $emailMessage->activities()->get()->each->delete();
    }

    /**
     * Handle the email message "restored" event.
     *
     * @return void
     */
    public function restored(EmailMessage $emailMessage)
    {
        if (! app()->runningInConsole()) {
            $emailMessage->user_restored_id = auth()->user()->id ?? null;
            $emailMessage->saveQuietly();
        }
    }
}
