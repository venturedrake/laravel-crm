<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Feature;
use VentureDrake\LaravelCrm\Models\FeatureStatus;
use VentureDrake\LaravelCrm\Services\NumberGeneratorService;

class FeatureObserver
{
    /**
     * Handle the feature "creating" event.
     *
     * @return void
     */
    public function creating(Feature $feature)
    {
        if (! $feature->external_id) {
            $feature->external_id = Uuid::uuid4()->toString();
        }

        if (! app()->runningInConsole()) {
            $feature->user_created_id = auth()->user()->id ?? null;
        }

        $feature->number = NumberGeneratorService::next(Feature::class, 1000);
        $feature->feature_id = 'F'.$feature->number;

        if (! $feature->feature_status_id) {
            $default = FeatureStatus::where('is_default', true)->first();

            if ($default) {
                $feature->feature_status_id = $default->id;
            }
        }
    }

    /**
     * Handle the feature "updating" event.
     *
     * @return void
     */
    public function updating(Feature $feature)
    {
        if (! app()->runningInConsole()) {
            $feature->user_updated_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the feature "deleting" event.
     *
     * @return void
     */
    public function deleting(Feature $feature)
    {
        if (! app()->runningInConsole()) {
            $feature->user_deleted_id = auth()->user()->id ?? null;
            $feature->saveQuietly();
        }
    }

    /**
     * Handle the feature "restored" event.
     *
     * @return void
     */
    public function restored(Feature $feature)
    {
        if (! app()->runningInConsole()) {
            $feature->user_deleted_id = null;
            $feature->saveQuietly();
        }
    }
}
