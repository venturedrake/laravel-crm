<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\SmsTemplate;

class SmsTemplateObserver
{
    public function creating(SmsTemplate $template)
    {
        if (! $template->external_id) {
            $template->external_id = Uuid::uuid4()->toString();
        }

        if (! app()->runningInConsole()) {
            $template->user_created_id = auth()->user()->id ?? null;
        }
    }

    public function updating(SmsTemplate $template)
    {
        if (! app()->runningInConsole()) {
            $template->user_updated_id = auth()->user()->id ?? null;
        }
    }

    public function deleting(SmsTemplate $template)
    {
        if (! app()->runningInConsole()) {
            $template->user_deleted_id = auth()->user()->id ?? null;
            $template->saveQuietly();
        }
    }
}
