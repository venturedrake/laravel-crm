<?php

namespace VentureDrake\LaravelCrm\Listeners;

use Lab404\AuthChecker\Events\DeviceCreated;

class NewAuthDevice
{
    public function handle(DeviceCreated $event)
    {
        // Create a notification for new devices
    }
}
