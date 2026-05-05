<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\EmailTemplate;

class EmailTemplateObserver
{
    public function creating(EmailTemplate $template)
    {
        if (! $template->external_id) {
            $template->external_id = Uuid::uuid4()->toString();
        }

        if (! app()->runningInConsole()) {
            $template->user_created_id = auth()->user()->id ?? null;
        }
    }

    public function updating(EmailTemplate $template)
    {
        if (! app()->runningInConsole()) {
            $template->user_updated_id = auth()->user()->id ?? null;
        }
    }

    public function deleting(EmailTemplate $template)
    {
        if (! app()->runningInConsole()) {
            $template->user_deleted_id = auth()->user()->id ?? null;
            $template->saveQuietly();
        }
    }
}
