<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Feature;
use VentureDrake\LaravelCrm\Models\FeatureComment;

class FeatureCommentObserver
{
    /**
     * Handle the feature comment "creating" event.
     *
     * @return void
     */
    public function creating(FeatureComment $comment)
    {
        if (! $comment->external_id) {
            $comment->external_id = Uuid::uuid4()->toString();
        }

        if (! app()->runningInConsole()) {
            $comment->user_created_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the feature comment "created" event.
     *
     * @return void
     */
    public function created(FeatureComment $comment)
    {
        Feature::whereKey($comment->feature_id)->increment('comments_count');
    }

    /**
     * Handle the feature comment "updating" event.
     *
     * @return void
     */
    public function updating(FeatureComment $comment)
    {
        if (! app()->runningInConsole()) {
            $comment->user_updated_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the feature comment "deleting" event.
     *
     * @return void
     */
    public function deleting(FeatureComment $comment)
    {
        if (! app()->runningInConsole()) {
            $comment->user_deleted_id = auth()->user()->id ?? null;
            $comment->saveQuietly();
        }
    }

    /**
     * Handle the feature comment "deleted" event.
     *
     * @return void
     */
    public function deleted(FeatureComment $comment)
    {
        Feature::whereKey($comment->feature_id)->decrement('comments_count');
    }

    /**
     * Handle the feature comment "restored" event.
     *
     * @return void
     */
    public function restored(FeatureComment $comment)
    {
        if (! app()->runningInConsole()) {
            $comment->user_deleted_id = null;
            $comment->saveQuietly();
        }

        Feature::whereKey($comment->feature_id)->increment('comments_count');
    }
}
