<?php

namespace VentureDrake\LaravelCrm;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Lab404\AuthChecker\Events\DeviceCreated;
use VentureDrake\LaravelCrm\Listeners\NewAuthDevice;

class LaravelCrmEventServiceProvider extends ServiceProvider
{
    protected $listen = [
        DeviceCreated::class => [
            NewAuthDevice::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }
}
