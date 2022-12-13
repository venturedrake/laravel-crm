<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\OrderProduct;

class OrderProductObserver
{
    /**
     * Handle the orderProduct "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\OrderProduct  $orderProduct
     * @return void
     */
    public function creating(OrderProduct $orderProduct)
    {
        $orderProduct->external_id = Uuid::uuid4()->toString();
    }

    /**
     * Handle the orderProduct "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\OrderProduct  $orderProduct
     * @return void
     */
    public function created(OrderProduct $orderProduct)
    {
        //
    }

    /**
     * Handle the orderProduct "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\OrderProduct  $orderProduct
     * @return void
     */
    public function updating(OrderProduct $orderProduct)
    {
        //
    }

    /**
     * Handle the orderProduct "updated" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\OrderProduct  $orderProduct
     * @return void
     */
    public function updated(OrderProduct $orderProduct)
    {
        //
    }

    /**
     * Handle the orderProduct "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\OrderProduct  $orderProduct
     * @return void
     */
    public function deleting(OrderProduct $orderProduct)
    {
        //
    }

    /**
     * Handle the orderProduct "deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\OrderProduct  $orderProduct
     * @return void
     */
    public function deleted(OrderProduct $orderProduct)
    {
        //
    }

    /**
     * Handle the orderProduct "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\OrderProduct  $orderProduct
     * @return void
     */
    public function restored(OrderProduct $orderProduct)
    {
        //
    }

    /**
     * Handle the orderProduct "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\OrderProduct  $orderProduct
     * @return void
     */
    public function forceDeleted(OrderProduct $orderProduct)
    {
        //
    }
}
