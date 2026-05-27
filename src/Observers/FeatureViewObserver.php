<?php

namespace VentureDrake\LaravelCrm\Observers;

use VentureDrake\LaravelCrm\Models\Feature;
use VentureDrake\LaravelCrm\Models\FeatureView;

class FeatureViewObserver
{
    /**
     * Handle the feature view "created" event.
     *
     * @return void
     */
    public function created(FeatureView $view)
    {
        Feature::whereKey($view->feature_id)->increment('views_count');
    }
}
