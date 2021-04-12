<?php

namespace VentureDrake\LaravelCrm\Traits;

trait HasCrmAccess
{
    public function hasCrmAccess()
    {
        return $this->crm_access;
    }
    
    public function isCrmOwner()
    {
        return config('laravel-crm.crm_owner') == $this->email;
    }
}
