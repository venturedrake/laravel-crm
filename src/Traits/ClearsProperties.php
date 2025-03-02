<?php

namespace VentureDrake\LaravelCrm\Traits;

/**
 * Reset all components properties
 */
trait ClearsProperties
{
    public function clear(): void
    {
        $this->reset();
    }
}
