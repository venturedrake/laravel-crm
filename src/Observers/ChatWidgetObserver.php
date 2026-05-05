<?php

namespace VentureDrake\LaravelCrm\Observers;

use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\ChatWidget;

class ChatWidgetObserver
{
    public function creating(ChatWidget $widget)
    {
        $widget->external_id = Uuid::uuid4()->toString();

        if (! $widget->public_key) {
            $widget->public_key = Str::random(40);
        }

        if (! app()->runningInConsole()) {
            $widget->user_created_id = auth()->user()->id ?? null;
        }
    }

    public function updating(ChatWidget $widget)
    {
        if (! app()->runningInConsole()) {
            $widget->user_updated_id = auth()->user()->id ?? null;
        }
    }

    public function deleting(ChatWidget $widget)
    {
        if (! app()->runningInConsole()) {
            $widget->user_deleted_id = auth()->user()->id ?? null;
            $widget->saveQuietly();
        }
    }
}
