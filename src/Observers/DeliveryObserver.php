<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Services\NumberGeneratorService;
use VentureDrake\LaravelCrm\Services\SettingService;

class DeliveryObserver
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
     * Handle the delivery "creating" event.
     *
     * @return void
     */
    public function creating(Delivery $delivery)
    {
        $delivery->external_id = Uuid::uuid4()->toString();

        if (! app()->runningInConsole()) {
            $delivery->user_created_id = auth()->user()->id ?? null;
        }

        $delivery->number = NumberGeneratorService::next(Delivery::class, 1000);

        $delivery->prefix = $this->settingService->get('delivery_prefix');
        $delivery->delivery_id = $delivery->prefix.$delivery->number;
    }

    /**
     * Handle the delivery "created" event.
     *
     * @return void
     */
    public function created(Delivery $delivery)
    {
        //
    }

    /**
     * Handle the delivery "updating" event.
     *
     * @return void
     */
    public function updating(Delivery $delivery)
    {
        if (! app()->runningInConsole()) {
            $delivery->user_updated_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the delivery "updated" event.
     *
     * @return void
     */
    public function updated(Delivery $delivery)
    {
        //
    }

    /**
     * Handle the delivery "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\Delivery  $delivery
     * @return void
     */
    public function deleting(Delivery $delivery)
    {
        if (! app()->runningInConsole()) {
            $delivery->user_deleted_id = auth()->user()->id ?? null;
            $delivery->saveQuietly();
        }
    }

    /**
     * Handle the delivery "deleted" event.
     *
     * @return void
     */
    public function deleted(Delivery $delivery)
    {
        //
    }

    /**
     * Handle the delivery "restored" event.
     *
     * @return void
     */
    public function restored(Delivery $delivery)
    {
        if (! app()->runningInConsole()) {
            $delivery->user_restored_id = auth()->user()->id ?? null;
            $delivery->saveQuietly();
        }
    }

    /**
     * Handle the delivery "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(Delivery $delivery)
    {
        //
    }
}
