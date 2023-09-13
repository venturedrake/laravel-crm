<?php

namespace VentureDrake\LaravelCrm\Traits;

trait HasCrmAddresses
{
    public function addresses()
    {
        return $this->morphMany(\VentureDrake\LaravelCrm\Models\Address::class, 'addressable');
    }

    public function getPrimaryAddress()
    {
        return $this->addresses()->where('primary', 1)->first();
    }
}
