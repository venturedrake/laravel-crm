<?php

namespace VentureDrake\LaravelCrm\Observers;

use VentureDrake\LaravelCrm\Models\Feature;
use VentureDrake\LaravelCrm\Models\FeatureVote;

class FeatureVoteObserver
{
    /**
     * Handle the feature vote "created" event.
     *
     * @return void
     */
    public function created(FeatureVote $vote)
    {
        Feature::whereKey($vote->feature_id)->increment('votes_count');
    }

    /**
     * Handle the feature vote "deleted" event.
     *
     * @return void
     */
    public function deleted(FeatureVote $vote)
    {
        Feature::whereKey($vote->feature_id)->decrement('votes_count');
    }
}
