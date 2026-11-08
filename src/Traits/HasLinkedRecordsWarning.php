<?php

namespace VentureDrake\LaravelCrm\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Lists the records that point at a contact, so the delete dialog can warn
 * before the contact goes. Deletion still goes ahead; the linked records keep
 * the reference and show the contact as deleted.
 */
trait HasLinkedRecordsWarning
{
    /**
     * The hasMany relations whose records point at this model.
     *
     * @return array<int, string>
     */
    abstract public function linkedRecordRelations(): array;

    /**
     * Each linked relation mapped to its count, keeping only counts above zero.
     * Soft-deleted records do not count.
     *
     * Reads a `{relation}_count` attribute when withCount() loaded one, so an
     * index page asks once rather than per row; otherwise queries.
     *
     * @return array<string, int>
     */
    public function linkedRecordCounts(): array
    {
        $counts = [];

        foreach ($this->linkedRecordRelations() as $relation) {
            $attribute = Str::snake($relation).'_count';

            $count = array_key_exists($attribute, $this->attributes)
                ? (int) $this->attributes[$attribute]
                : $this->{$relation}()->count();

            if ($count > 0) {
                $counts[$relation] = $count;
            }
        }

        return $counts;
    }

    /**
     * The warning for the delete dialog, or null when nothing is linked.
     * e.g. "This person is linked to 2 deals and 1 quote. ..."
     */
    public function deleteWarning(): ?string
    {
        $counts = $this->linkedRecordCounts();

        if ($counts === []) {
            return null;
        }

        $parts = [];

        foreach ($counts as $relation => $count) {
            // The lang keys are the snake-cased relation (`purchase_orders`,
            // `people`) and its singular (`purchase_order`, `person`).
            $key = Str::snake($relation);

            $parts[] = $count.' '.__('laravel-crm::lang.'.($count === 1 ? Str::singular($key) : $key));
        }

        return ucfirst(__('laravel-crm::lang.delete_linked_records_warning', [
            'model' => __('laravel-crm::lang.'.Str::snake(class_basename($this))),
            'records' => Arr::join($parts, ', ', ' '.__('laravel-crm::lang.and').' '),
        ]));
    }
}
