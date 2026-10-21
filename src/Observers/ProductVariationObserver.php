<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\ProductVariation;

/**
 * Mirrors ProductObserver, minus its runningInConsole() guard: auth()->id()
 * is already null in a console without a user, and the guard would also hide
 * the user on variations created from a queued job or a test acting as one.
 */
class ProductVariationObserver
{
    /**
     * Handle the product variation "creating" event.
     *
     * @return void
     */
    public function creating(ProductVariation $productVariation)
    {
        $productVariation->external_id = Uuid::uuid4()->toString();
        $productVariation->user_created_id = auth()->id();
    }

    /**
     * Handle the product variation "updating" event.
     *
     * @return void
     */
    public function updating(ProductVariation $productVariation)
    {
        if (auth()->hasUser()) {
            $productVariation->user_updated_id = auth()->id();
        }
    }

    /**
     * Handle the product variation "deleting" event.
     *
     * @return void
     */
    public function deleting(ProductVariation $productVariation)
    {
        if (auth()->hasUser() && ! $productVariation->isForceDeleting()) {
            $productVariation->user_deleted_id = auth()->id();
            $productVariation->saveQuietly();
        }
    }

    /**
     * Handle the product variation "restored" event.
     *
     * @return void
     */
    public function restored(ProductVariation $productVariation)
    {
        if (auth()->hasUser()) {
            $productVariation->user_deleted_id = null;
            $productVariation->user_restored_id = auth()->id();
            $productVariation->saveQuietly();
        }
    }
}
