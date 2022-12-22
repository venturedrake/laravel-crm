<?php

namespace VentureDrake\LaravelCrm\Traits;

trait HasCrmActivities
{
    public function activities()
    {
        return $this->morphMany(\VentureDrake\LaravelCrm\Models\Activity::class, 'timelineable');
    }

    public function tasks()
    {
        return $this->morphMany(\VentureDrake\LaravelCrm\Models\Task::class, 'taskable');
    }

    public function calls()
    {
        return $this->morphMany(\VentureDrake\LaravelCrm\Models\Call::class, 'callable');
    }

    public function meetings()
    {
        return $this->morphMany(\VentureDrake\LaravelCrm\Models\Meeting::class, 'meetingable');
    }

    public function lunches()
    {
        return $this->morphMany(\VentureDrake\LaravelCrm\Models\Lunch::class, 'lunchable');
    }

    public function notes()
    {
        return $this->morphMany(\VentureDrake\LaravelCrm\Models\Note::class, 'noteable');
    }

    public function files()
    {
        return $this->morphMany(\VentureDrake\LaravelCrm\Models\File::class, 'fileable');
    }
}
