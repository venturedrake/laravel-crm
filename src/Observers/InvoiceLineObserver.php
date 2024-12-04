<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\InvoiceLine;

class InvoiceLineObserver
{
    /**
     * Handle the invoiceLine "creating" event.
     *
     * @return void
     */
    public function creating(InvoiceLine $invoiceLine)
    {
        $invoiceLine->external_id = Uuid::uuid4()->toString();
    }

    /**
     * Handle the invoiceLine "created" event.
     *
     * @return void
     */
    public function created(InvoiceLine $invoiceLine)
    {
        //
    }

    /**
     * Handle the invoiceLine "updating" event.
     *
     * @return void
     */
    public function updating(InvoiceLine $invoiceLine)
    {
        //
    }

    /**
     * Handle the invoiceLine "updated" event.
     *
     * @return void
     */
    public function updated(InvoiceLine $invoiceLine)
    {
        //
    }

    /**
     * Handle the invoiceLine "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\InvoiceLine  $invoiceLine
     * @return void
     */
    public function deleting(InvoiceLine $invoiceLine)
    {
        //
    }

    /**
     * Handle the invoiceLine "deleted" event.
     *
     * @return void
     */
    public function deleted(InvoiceLine $invoiceLine)
    {
        //
    }

    /**
     * Handle the invoiceLine "restored" event.
     *
     * @return void
     */
    public function restored(InvoiceLine $invoiceLine)
    {
        //
    }

    /**
     * Handle the invoiceLine "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(InvoiceLine $invoiceLine)
    {
        //
    }
}
