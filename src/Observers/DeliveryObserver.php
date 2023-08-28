<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Delivery;
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
     * @param  \VentureDrake\LaravelCrm\Models\Delivery  $delivery
     * @return void
     */
    public function creating(Delivery $delivery)
    {
        $delivery->external_id = Uuid::uuid4()->toString();

        if (! app()->runningInConsole()) {
            $delivery->user_created_id = auth()->user()->id ?? null;
        }

        if($lastDelivery = Delivery::withTrashed()->orderBy('number', 'DESC')->first()) {
            $delivery->number = $lastDelivery->number + 1;
        } else {
            $delivery->number = 1000;
        }

        $delivery->prefix = $this->settingService->get('delivery_prefix')->value;
        $delivery->delivery_id = $delivery->prefix.$delivery->number;
    }

    /**
     * Handle the delivery "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Delivery  $delivery
     * @return void
     */
    public function created(Delivery $delivery)
    {
        //
    }

    /**
     * Handle the delivery "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Delivery  $delivery
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
     * @param  \VentureDrake\LaravelCrm\Models\Delivery  $delivery
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
     * @param  \VentureDrake\LaravelCrm\Models\Delivery  $delivery
     * @return void
     */
    public function deleted(Delivery $delivery)
    {
        //
    }

    /**
     * Handle the delivery "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Delivery  $delivery
     * @return void
     */
    public function restored(Delivery $delivery)
    {
        if (! app()->runningInConsole()) {
            $delivery->user_deleted_id = auth()->user()->id ?? null;
            $delivery->saveQuietly();
        }
    }

    /**
     * Handle the delivery "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Delivery  $delivery
     * @return void
     */
    public function forceDeleted(Delivery $delivery)
    {
        //
    }
}
