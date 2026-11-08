<?php

namespace VentureDrake\LaravelCrm\Http\Requests\Api\V2\Concerns;

use Illuminate\Validation\Validator;
use VentureDrake\LaravelCrm\Http\Rules\NotBeforeDate;

/**
 * Rejects an end date that falls on an earlier day than its start date, such
 * as an invoice due before it is issued.
 *
 * A field left out of the request keeps its stored value, so the check runs
 * on the dates the record will have after the update. If only the start date
 * was sent, the error goes on the start date, as that is what the client has
 * to change. If neither was sent, nothing is checked, so a record already
 * stored with its dates out of order can still be updated otherwise.
 */
trait ValidatesDateOrder
{
    /**
     * @param  mixed  $storedStart  the record's stored start date, null on store
     * @param  mixed  $storedEnd  the record's stored end date, null on store
     * @param  string|null  $timezone  see NotBeforeDate - for an end date stored converted
     */
    protected function dateOrderCheck(
        string $startKey,
        string $endKey,
        mixed $storedStart = null,
        mixed $storedEnd = null,
        ?string $timezone = null,
    ): callable {
        return function (Validator $validator) use ($startKey, $endKey, $storedStart, $storedEnd, $timezone) {
            // Neither date sent: the request isn't changing them, so a record
            // already stored out of order must still take unrelated updates.
            if (! $this->has($startKey) && ! $this->has($endKey)) {
                return;
            }

            if ($validator->errors()->has($startKey) || $validator->errors()->has($endKey)) {
                return;
            }

            $start = $this->has($startKey) ? $this->input($startKey) : $storedStart;
            $end = $this->has($endKey) ? $this->input($endKey) : $storedEnd;

            if (! NotBeforeDate::isBefore($end, $start, $timezone)) {
                return;
            }

            $field = $this->has($endKey) ? $endKey : $startKey;

            $validator->errors()->add($field, $field === $endKey
                ? "The {$endKey} can't be before the {$startKey}."
                : "The {$startKey} can't be after the {$endKey}.");
        };
    }
}
