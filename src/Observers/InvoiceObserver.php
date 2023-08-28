<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Services\SettingService;

class InvoiceObserver
{
    /**
     * @var SettingService
     */
    private $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Handle the invoice "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Invoice  $invoice
     * @return void
     */
    public function creating(Invoice $invoice)
    {
        $invoice->external_id = Uuid::uuid4()->toString();

        if (! app()->runningInConsole()) {
            $invoice->user_created_id = auth()->user()->id ?? null;
        }

        if($lastInvoice = Invoice::withTrashed()->orderBy('number', 'DESC')->first()) {
            $invoice->number = $lastInvoice->number + 1;
        } else {
            $invoice->number = 1000;
        }

        $invoice->prefix = $this->settingService->get('invoice_prefix')->value;
        $invoice->invoice_id = $invoice->prefix.$invoice->number;
    }

    /**
     * Handle the invoice "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Invoice  $invoice
     * @return void
     */
    public function created(Invoice $invoice)
    {
        //
    }

    /**
     * Handle the invoice "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Invoice  $invoice
     * @return void
     */
    public function updating(Invoice $invoice)
    {
        if (! app()->runningInConsole()) {
            $invoice->user_updated_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the invoice "updated" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Invoice  $invoice
     * @return void
     */
    public function updated(Invoice $invoice)
    {
        //
    }

    /**
     * Handle the invoice "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\Invoice  $invoice
     * @return void
     */
    public function deleting(Invoice $invoice)
    {
        if (! app()->runningInConsole()) {
            $invoice->user_deleted_id = auth()->user()->id ?? null;
            $invoice->saveQuietly();
        }
    }

    /**
     * Handle the invoice "deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Invoice  $invoice
     * @return void
     */
    public function deleted(Invoice $invoice)
    {
        //
    }

    /**
     * Handle the invoice "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Invoice  $invoice
     * @return void
     */
    public function restored(Invoice $invoice)
    {
        if (! app()->runningInConsole()) {
            $invoice->user_deleted_id = auth()->user()->id ?? null;
            $invoice->saveQuietly();
        }
    }

    /**
     * Handle the invoice "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Invoice  $invoice
     * @return void
     */
    public function forceDeleted(Invoice $invoice)
    {
        //
    }
}
