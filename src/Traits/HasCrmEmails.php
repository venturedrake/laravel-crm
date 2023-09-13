<?php

namespace VentureDrake\LaravelCrm\Traits;

trait HasCrmEmails
{
    public function emails()
    {
        return $this->morphMany(\VentureDrake\LaravelCrm\Models\Email::class, 'emailable');
    }

    public function getPrimaryEmail()
    {
        return $this->emails()->where('primary', 1)->first();
    }
}
