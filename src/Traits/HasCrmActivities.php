<?php

namespace VentureDrake\LaravelCrm\Traits;

use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Models\Call;
use VentureDrake\LaravelCrm\Models\EmailMessage;
use VentureDrake\LaravelCrm\Models\File;
use VentureDrake\LaravelCrm\Models\Lunch;
use VentureDrake\LaravelCrm\Models\Meeting;
use VentureDrake\LaravelCrm\Models\Note;
use VentureDrake\LaravelCrm\Models\Task;

trait HasCrmActivities
{
    public function activities()
    {
        return $this->morphMany(Activity::class, 'timelineable');
    }

    public function tasks()
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    public function calls()
    {
        return $this->morphMany(Call::class, 'callable');
    }

    /**
     * Emails logged against the record. Not emails(), which on a person or
     * an organisation is the list of their email addresses.
     */
    public function emailMessages()
    {
        return $this->morphMany(EmailMessage::class, 'emailable');
    }

    public function meetings()
    {
        return $this->morphMany(Meeting::class, 'meetingable');
    }

    public function lunches()
    {
        return $this->morphMany(Lunch::class, 'lunchable');
    }

    public function notes()
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }
}
