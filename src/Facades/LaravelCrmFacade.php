<?php

namespace VentureDrake\LaravelCrm\Facades;

use Illuminate\Support\Facades\Facade;
use VentureDrake\LaravelCrm\LaravelCrm;

/**
 * @see LaravelCrm
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
