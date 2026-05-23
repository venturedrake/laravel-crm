<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Monitor;

class MonitorObserver
{
    public function creating(Monitor $monitor)
    {
        $monitor->external_id = Uuid::uuid4()->toString();

        if ($monitor->url) {
            $monitor->host = parse_url($monitor->url, PHP_URL_HOST);
        }

        if (! app()->runningInConsole()) {
            $monitor->user_created_id = auth()->user()->id ?? null;
        }
    }

    public function created(Monitor $monitor)
    {
        //
    }

    public function updating(Monitor $monitor)
    {
        if ($monitor->isDirty('url')) {
            $monitor->host = $monitor->url ? parse_url($monitor->url, PHP_URL_HOST) : null;
            $monitor->down_since_at = null;
            $monitor->notified_at = null;
            $monitor->ssl_notified_at = null;
        }

        if ($monitor->isDirty('is_active')) {
            $monitor->down_since_at = null;
            $monitor->notified_at = null;
            $monitor->ssl_notified_at = null;
        }

        if (! app()->runningInConsole()) {
            $monitor->user_updated_id = auth()->user()->id ?? null;
        }
    }

    public function updated(Monitor $monitor)
    {
        //
    }

    public function deleting(Monitor $monitor)
    {
        if (! app()->runningInConsole()) {
            $monitor->user_deleted_id = auth()->user()->id ?? null;
            $monitor->saveQuietly();
        }
    }

    public function deleted(Monitor $monitor)
    {
        //
    }

    public function restored(Monitor $monitor)
    {
        if (! app()->runningInConsole()) {
            $monitor->user_deleted_id = null;
            $monitor->user_restored_id = auth()->user()->id ?? null;
            $monitor->saveQuietly();
        }
    }

    public function forceDeleted(Monitor $monitor)
    {
        //
    }
}
