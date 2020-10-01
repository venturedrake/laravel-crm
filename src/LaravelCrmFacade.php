<?php

namespace Venturedrake\LaravelCrm;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Venturedrake\LaravelCrm\Skeleton\SkeletonClass
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
