<?php

namespace VentureDrake\LaravelCrm\Traits;

trait HasCrmPhones
{
    public function phones()
    {
        return $this->morphMany(\VentureDrake\LaravelCrm\Models\Phone::class, 'phoneable');
    }

    public function getPrimaryPhone()
    {
        return $this->phones()->where('primary', 1)->first();
    }
}
