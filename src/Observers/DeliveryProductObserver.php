<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\DeliveryProduct;

class DeliveryProductObserver
{
    /**
     * Handle the deliveryProduct "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\DeliveryProduct  $deliveryProduct
     * @return void
     */
    public function creating(DeliveryProduct $deliveryProduct)
    {
        $deliveryProduct->external_id = Uuid::uuid4()->toString();
    }

    /**
     * Handle the deliveryProduct "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\DeliveryProduct  $deliveryProduct
     * @return void
     */
    public function created(DeliveryProduct $deliveryProduct)
    {
        //
    }

    /**
     * Handle the deliveryProduct "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\DeliveryProduct  $deliveryProduct
     * @return void
     */
    public function updating(DeliveryProduct $deliveryProduct)
    {
        //
    }

    /**
     * Handle the deliveryProduct "updated" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\DeliveryProduct  $deliveryProduct
     * @return void
     */
    public function updated(DeliveryProduct $deliveryProduct)
    {
        //
    }

    /**
     * Handle the deliveryProduct "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\DeliveryProduct  $deliveryProduct
     * @return void
     */
    public function deleting(DeliveryProduct $deliveryProduct)
    {
        //
    }

    /**
     * Handle the deliveryProduct "deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\DeliveryProduct  $deliveryProduct
     * @return void
     */
    public function deleted(DeliveryProduct $deliveryProduct)
    {
        //
    }

    /**
     * Handle the deliveryProduct "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\DeliveryProduct  $deliveryProduct
     * @return void
     */
    public function restored(DeliveryProduct $deliveryProduct)
    {
        //
    }

    /**
     * Handle the deliveryProduct "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\DeliveryProduct  $deliveryProduct
     * @return void
     */
    public function forceDeleted(DeliveryProduct $deliveryProduct)
    {
        //
    }
}
