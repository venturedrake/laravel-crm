<?php

namespace VentureDrake\LaravelCrm\Traits;

/**
 * Reset a component's filters. The Reset button of every filter drawer calls this.
 */
trait ClearsProperties
{
    public function clear(): void
    {
        $this->reset(...$this->clearableProperties());
    }

    /**
     * The properties to reset. Empty means every public one, which a component that
     * keeps anything but filters in its properties has to narrow.
     *
     * @return array<int, string>
     */
    protected function clearableProperties(): array
    {
        return [];
    }
}
