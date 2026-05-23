<?php

namespace VentureDrake\LaravelCrm\Repositories;

use VentureDrake\LaravelCrm\Models\Monitor;

class MonitorRepository
{
    public function all()
    {
        return Monitor::all();
    }

    public function find($id)
    {
        return Monitor::find($id);
    }
}
