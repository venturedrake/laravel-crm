<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\InvoiceLine;

class InvoiceLineObserver
{
    /**
     * Handle the invoiceLine "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\InvoiceLine  $invoiceLine
     * @return void
     */
    public function creating(InvoiceLine $invoiceLine)
    {
        $invoiceLine->external_id = Uuid::uuid4()->toString();
    }

    /**
     * Handle the invoiceLine "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\InvoiceLine  $invoiceLine
     * @return void
     */
    public function created(InvoiceLine $invoiceLine)
    {
        //
    }

    /**
     * Handle the invoiceLine "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\InvoiceLine  $invoiceLine
     * @return void
     */
    public function updating(InvoiceLine $invoiceLine)
    {
        //
    }

    /**
     * Handle the invoiceLine "updated" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\InvoiceLine  $invoiceLine
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
     * @param  \VentureDrake\LaravelCrm\Models\InvoiceLine  $invoiceLine
     * @return void
     */
    public function deleted(InvoiceLine $invoiceLine)
    {
        //
    }

    /**
     * Handle the invoiceLine "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\InvoiceLine  $invoiceLine
     * @return void
     */
    public function restored(InvoiceLine $invoiceLine)
    {
        //
    }

    /**
     * Handle the invoiceLine "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\InvoiceLine  $invoiceLine
     * @return void
     */
    public function forceDeleted(InvoiceLine $invoiceLine)
    {
        //
    }
}
