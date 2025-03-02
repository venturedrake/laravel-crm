<?php

namespace VentureDrake\LaravelCrm\Traits;

/**
 * Reset table pagination when any component property changes
 */
trait ResetsPaginationWhenPropsChanges
{
    public function updated($property): void
    {
        // Do not reset pagination if toggling `expand rows`, because it is `.live`
        $isExpanding = str($property)->contains('expanded');

        if (! is_array($property) && ! $isExpanding && $property != '') {
            $this->resetPage();
        }
    }
}
