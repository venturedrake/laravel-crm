<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Services\SettingService;

class PurchaseOrderObserver
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
     * Handle the purchaseOrder "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\PurchaseOrder  $purchaseOrder
     * @return void
     */
    public function creating(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->external_id = Uuid::uuid4()->toString();

        if (! app()->runningInConsole()) {
            $purchaseOrder->user_created_id = auth()->user()->id ?? null;
        }

        if($lastPurchaseOrder = PurchaseOrder::withTrashed()->orderBy('number', 'DESC')->first()) {
            $purchaseOrder->number = $lastPurchaseOrder->number + 1;
        } else {
            $purchaseOrder->number = 1000;
        }

        $purchaseOrder->prefix = $this->settingService->get('purchase_order_prefix')->value;
        $purchaseOrder->purchase_order_id = $purchaseOrder->prefix.$purchaseOrder->number;
    }

    /**
     * Handle the purchaseOrder "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\PurchaseOrder  $purchaseOrder
     * @return void
     */
    public function created(PurchaseOrder $purchaseOrder)
    {
        //
    }

    /**
     * Handle the purchaseOrder "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\PurchaseOrder  $purchaseOrder
     * @return void
     */
    public function updating(PurchaseOrder $purchaseOrder)
    {
        if (! app()->runningInConsole()) {
            $purchaseOrder->user_updated_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the purchaseOrder "updated" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\PurchaseOrder  $purchaseOrder
     * @return void
     */
    public function updated(PurchaseOrder $purchaseOrder)
    {
        //
    }

    /**
     * Handle the purchaseOrder "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\PurchaseOrder  $purchaseOrder
     * @return void
     */
    public function deleting(PurchaseOrder $purchaseOrder)
    {
        if (! app()->runningInConsole()) {
            $purchaseOrder->user_deleted_id = auth()->user()->id ?? null;
            $purchaseOrder->saveQuietly();
        }
    }

    /**
     * Handle the purchaseOrder "deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\PurchaseOrder  $purchaseOrder
     * @return void
     */
    public function deleted(PurchaseOrder $purchaseOrder)
    {
        //
    }

    /**
     * Handle the purchaseOrder "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\PurchaseOrder  $purchaseOrder
     * @return void
     */
    public function restored(PurchaseOrder $purchaseOrder)
    {
        if (! app()->runningInConsole()) {
            $purchaseOrder->user_deleted_id = auth()->user()->id ?? null;
            $purchaseOrder->saveQuietly();
        }
    }

    /**
     * Handle the purchaseOrder "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\PurchaseOrder  $purchaseOrder
     * @return void
     */
    public function forceDeleted(PurchaseOrder $purchaseOrder)
    {
        //
    }
}
