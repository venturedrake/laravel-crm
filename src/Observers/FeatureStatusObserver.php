<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\FeatureStatus;

class FeatureStatusObserver
{
    public function creating(FeatureStatus $featureStatus)
    {
        if (! $featureStatus->external_id) {
            $featureStatus->external_id = Uuid::uuid4()->toString();
        }

        if (! app()->runningInConsole()) {
            $featureStatus->user_created_id = auth()->user()->id ?? null;
        }
    }

    public function updating(FeatureStatus $featureStatus)
    {
        if (! app()->runningInConsole()) {
            $featureStatus->user_updated_id = auth()->user()->id ?? null;
        }
    }

    public function deleting(FeatureStatus $featureStatus)
    {
        if (! app()->runningInConsole()) {
            $featureStatus->user_deleted_id = auth()->user()->id ?? null;
            $featureStatus->saveQuietly();
        }
    }

    public function restored(FeatureStatus $featureStatus)
    {
        if (! app()->runningInConsole()) {
            $featureStatus->user_deleted_id = null;
            $featureStatus->saveQuietly();
        }
    }
}
