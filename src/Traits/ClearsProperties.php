<?php

namespace VentureDrake\LaravelCrm\Traits;

/**
 * Reset a component's filters. The Reset button of every filter drawer calls this.
 */
trait ClearsProperties
{
    public function clear(): void
    {
        $properties = $this->clearableProperties();

        // reset() with no arguments resets every public property, including mount state.
        if ($properties !== []) {
            $this->reset(...$properties);
        }

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /**
     * Only filter state is reset; mounted settings, sort order and models are retained.
     * Boards may override this to also clear their paging state.
     *
     * @return array<int, string>
     */
    protected function clearableProperties(): array
    {
        return array_values(array_filter([
            'search', 'user_id', 'label_id', 'lead_source_id', 'status',
            'feature_status_id', 'is_public', 'role_id', 'crm_access',
            'userSearch', 'showFilters',
        ], fn (string $property) => property_exists($this, $property)));
    }
}
