<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\XeroItem;

class XeroItemObserver
{
    /**
     * Handle the xeroItem "creating" event.
     *
     * @return void
     */
    public function creating(XeroItem $xeroItem)
    {
        $xeroItem->external_id = Uuid::uuid4()->toString();
    }

    /**
     * Handle the xeroItem "created" event.
     *
     * @return void
     */
    public function created(XeroItem $xeroItem)
    {
        //
    }

    /**
     * Handle the xeroItem "updating" event.
     *
     * @return void
     */
    public function updating(XeroItem $xeroItem)
    {
        //
    }

    /**
     * Handle the xeroItem "updated" event.
     *
     * @return void
     */
    public function updated(XeroItem $xeroItem)
    {
        //
    }

    /**
     * Handle the xeroItem "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\XeroItem  $xeroItem
     * @return void
     */
    public function deleting(XeroItem $xeroItem)
    {
        //
    }

    /**
     * Handle the xeroItem "deleted" event.
     *
     * @return void
     */
    public function deleted(XeroItem $xeroItem)
    {
        //
    }

    /**
     * Handle the xeroItem "restored" event.
     *
     * @return void
     */
    public function restored(XeroItem $xeroItem)
    {
        //
    }

    /**
     * Handle the xeroItem "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(XeroItem $xeroItem)
    {
        //
    }
}
