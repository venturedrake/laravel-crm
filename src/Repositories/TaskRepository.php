<?php

namespace VentureDrake\LaravelCrm\Repositories;

use VentureDrake\LaravelCrm\Models\Task;

class TaskRepository
{
    public function all()
    {
        return Task::all();
    }

    public function find($id)
    {
        return Task::find($id);
    }
}

