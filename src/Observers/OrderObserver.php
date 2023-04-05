<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Order;

class OrderObserver
{
    /**
     * Handle the order "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Order  $order
     * @return void
     */
    public function creating(Order $order)
    {
        $order->external_id = Uuid::uuid4()->toString();

        if (! app()->runningInConsole()) {
            $order->user_created_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the order "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Order  $order
     * @return void
     */
    public function created(Order $order)
    {
        /*if ($order->organisation && ! $order->organisation->client) {
            $order->organisation->client()->create([
                'user_owner_id' => $order->organisation->user_owner_id,
            ]);
        }*/
    }

    /**
     * Handle the order "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Order  $order
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
     * @param  \VentureDrake\LaravelCrm\Models\Order  $order
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
     * @param  \VentureDrake\LaravelCrm\Models\Order  $order
     * @return void
     */
    public function deleted(Order $order)
    {
        //
    }

    /**
     * Handle the order "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Order  $order
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
     * @param  \VentureDrake\LaravelCrm\Models\Order  $order
     * @return void
     */
    public function forceDeleted(Order $order)
    {
        //
    }
}
