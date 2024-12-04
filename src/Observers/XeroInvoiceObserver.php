<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\XeroInvoice;

class XeroInvoiceObserver
{
    /**
     * Handle the xeroInvoice "creating" event.
     *
     * @return void
     */
    public function creating(XeroInvoice $xeroInvoice)
    {
        $xeroInvoice->external_id = Uuid::uuid4()->toString();
    }

    /**
     * Handle the xeroInvoice "created" event.
     *
     * @return void
     */
    public function created(XeroInvoice $xeroInvoice)
    {
        //
    }

    /**
     * Handle the xeroInvoice "updating" event.
     *
     * @return void
     */
    public function updating(XeroInvoice $xeroInvoice)
    {
        //
    }

    /**
     * Handle the xeroInvoice "updated" event.
     *
     * @return void
     */
    public function updated(XeroInvoice $xeroInvoice)
    {
        //
    }

    /**
     * Handle the xeroInvoice "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\XeroInvoice  $xeroInvoice
     * @return void
     */
    public function deleting(XeroInvoice $xeroInvoice)
    {
        //
    }

    /**
     * Handle the xeroInvoice "deleted" event.
     *
     * @return void
     */
    public function deleted(XeroInvoice $xeroInvoice)
    {
        //
    }

    /**
     * Handle the xeroInvoice "restored" event.
     *
     * @return void
     */
    public function restored(XeroInvoice $xeroInvoice)
    {
        //
    }

    /**
     * Handle the xeroInvoice "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(XeroInvoice $xeroInvoice)
    {
        //
    }
}
