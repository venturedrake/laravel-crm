<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\XeroPurchaseOrder;

class XeroPurchaseOrderObserver
{
    /**
     * Handle the xeroPurchaseOrder "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\XeroPurchaseOrder  $xeroPurchaseOrder
     * @return void
     */
    public function creating(XeroPurchaseOrder $xeroPurchaseOrder)
    {
        $xeroPurchaseOrder->external_id = Uuid::uuid4()->toString();
    }

    /**
     * Handle the xeroPurchaseOrder "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\XeroPurchaseOrder  $xeroPurchaseOrder
     * @return void
     */
    public function created(XeroPurchaseOrder $xeroPurchaseOrder)
    {
        //
    }

    /**
     * Handle the xeroPurchaseOrder "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\XeroPurchaseOrder  $xeroPurchaseOrder
     * @return void
     */
    public function updating(XeroPurchaseOrder $xeroPurchaseOrder)
    {
        //
    }

    /**
     * Handle the xeroPurchaseOrder "updated" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\XeroPurchaseOrder  $xeroPurchaseOrder
     * @return void
     */
    public function updated(XeroPurchaseOrder $xeroPurchaseOrder)
    {
        //
    }

    /**
     * Handle the xeroPurchaseOrder "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\XeroPurchaseOrder  $xeroPurchaseOrder
     * @return void
     */
    public function deleting(XeroPurchaseOrder $xeroPurchaseOrder)
    {
        //
    }

    /**
     * Handle the xeroPurchaseOrder "deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\XeroPurchaseOrder  $xeroPurchaseOrder
     * @return void
     */
    public function deleted(XeroPurchaseOrder $xeroPurchaseOrder)
    {
        //
    }

    /**
     * Handle the xeroPurchaseOrder "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\XeroPurchaseOrder  $xeroPurchaseOrder
     * @return void
     */
    public function restored(XeroPurchaseOrder $xeroPurchaseOrder)
    {
        //
    }

    /**
     * Handle the xeroPurchaseOrder "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\XeroPurchaseOrder  $xeroPurchaseOrder
     * @return void
     */
    public function forceDeleted(XeroPurchaseOrder $xeroPurchaseOrder)
    {
        //
    }
}
