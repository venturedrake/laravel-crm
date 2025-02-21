<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Services\SettingService;

class OrderObserver
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
     * Handle the order "creating" event.
     *
     * @return void
     */
    public function creating(Order $order)
    {
        $order->external_id = Uuid::uuid4()->toString();

        if (! app()->runningInConsole()) {
            $order->user_created_id = auth()->user()->id ?? null;
        }

        if ($lastOrder = Order::withTrashed()->orderBy('number', 'DESC')->first()) {
            $order->number = $lastOrder->number + 1;
        } else {
            $order->number = 1000;
        }

        $order->prefix = $this->settingService->get('order_prefix')->value;
        $order->order_id = $order->prefix.$order->number;
    }

    /**
     * Handle the order "created" event.
     *
     * @return void
     */
    public function created(Order $order)
    {
        /*if ($order->organization && ! $order->organization->client) {
            $order->organization->client()->create([
                'user_owner_id' => $order->organization->user_owner_id,
            ]);
        }*/
    }

    /**
     * Handle the order "updating" event.
     *
     * @return void
     */
    public function updating(Order $order)
    {
        if (! app()->runningInConsole()) {
            $order->user_updated_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the order "updated" event.
     *
     * @return void
     */
    public function updated(Order $order)
    {
        //
    }

    /**
     * Handle the order "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\Order  $order
     * @return void
     */
    public function deleting(Order $order)
    {
        if (! app()->runningInConsole()) {
            $order->user_deleted_id = auth()->user()->id ?? null;
            $order->saveQuietly();
        }
    }

    /**
     * Handle the order "deleted" event.
     *
     * @return void
     */
    public function deleted(Order $order)
    {
        //
    }

    /**
     * Handle the order "restored" event.
     *
     * @return void
     */
    public function restored(Order $order)
    {
        if (! app()->runningInConsole()) {
            $order->user_deleted_id = auth()->user()->id ?? null;
            $order->saveQuietly();
        }
    }

    /**
     * Handle the order "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(Order $order)
    {
        //
    }
}
