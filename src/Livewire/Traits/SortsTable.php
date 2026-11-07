<?php

namespace VentureDrake\LaravelCrm\Livewire\Traits;

trait SortsTable
{
    /**
     * URL state may still contain an old, unsupported display key.
     * Only advertised database columns may reach orderBy().
     */
    protected function tableSort(): array
    {
        $columns = collect($this->headers())
            ->filter(fn (array $header) => $header['sortable'] ?? true)
            ->pluck('key')->all();
        $column = $this->sortBy['column'] ?? 'created_at';
        $direction = $this->sortBy['direction'] ?? 'desc';

        return [
            in_array($column, $columns, true) ? $column : 'created_at',
            in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc',
        ];
    }
}
