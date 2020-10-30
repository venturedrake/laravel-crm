<?php

namespace VentureDrake\LaravelCrm\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \VentureDrake\LaravelCrm\LaravelCrm
 */
class LaravelCrmFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-crm';
    }
}
