<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\MonitorCheck;

class MonitorCheckObserver
{
    public function creating(MonitorCheck $check)
    {
        $check->external_id = Uuid::uuid4()->toString();

        if (! $check->team_id && $check->monitor) {
            $check->team_id = $check->monitor->team_id;
        }
    }
}
