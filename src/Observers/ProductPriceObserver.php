<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\ProductPrice;

class ProductPriceObserver
{
    /**
     * Handle the product "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\ProductPrice  $product
     * @return void
     */
    public function creating(ProductPrice $product)
    {
        $product->external_id = Uuid::uuid4()->toString();
    }

    /**
     * Handle the product "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\ProductPrice  $product
     * @return void
     */
    public function created(ProductPrice $product)
    {
        //
    }

    /**
     * Handle the product "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\ProductPrice  $product
     * @return void
     */
    public function updating(ProductPrice $product)
    {
        //
    }

    /**
     * Handle the product "updated" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\ProductPrice  $product
     * @return void
     */
    public function updated(ProductPrice $product)
    {
        //
    }

    /**
     * Handle the product "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\ProductPrice  $product
     * @return void
     */
    public function deleting(ProductPrice $product)
    {
        //
    }

    /**
     * Handle the product "deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\ProductPrice  $product
     * @return void
     */
    public function deleted(ProductPrice $product)
    {
        //
    }

    /**
     * Handle the product "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\ProductPrice  $product
     * @return void
     */
    public function restored(ProductPrice $product)
    {
        //
    }

    /**
     * Handle the product "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\ProductPrice  $product
     * @return void
     */
    public function forceDeleted(ProductPrice $product)
    {
        //
    }
}
