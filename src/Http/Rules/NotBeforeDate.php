<?php

namespace VentureDrake\LaravelCrm\Http\Rules;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Throwable;

/**
 * Reject an end date (an invoice's due date, a quote's expiry, a purchase
 * order's delivery date) that falls on an earlier day than its start date.
 *
 * Days are compared, not timestamps: a document due the day it is issued is
 * valid, and so is an API call that sends the issue date at 14:23 and the due
 * date at 00:00 on the same day. A blank or unparseable date on either side
 * passes - these dates are optional, and the format rules report bad input.
 */
class NotBeforeDate implements ValidationRule
{
    /**
     * @param  mixed  $start  the start date: a string, a DateTimeInterface or null
     * @param  string  $startLabel  how the start date is named in the message, e.g. "issue date"
     * @param  string|null  $timezone  the timezone the end date is converted to before its day is
     *                                 taken, for an end date that is stored converted
     */
    public function __construct(
        protected mixed $start,
        protected string $startLabel,
        protected ?string $timezone = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (static::isBefore($value, $this->start, $this->timezone)) {
            $fail(__('laravel-crm::lang.date_not_before', ['date' => $this->startLabel]));
        }
    }

    /**
     * Whether $end falls on an earlier day than $start. False when either is
     * blank or does not parse.
     */
    public static function isBefore(mixed $end, mixed $start, ?string $timezone = null): bool
    {
        $end = static::parse($end);
        $start = static::parse($start);

        if (! $end || ! $start) {
            return false;
        }

        if ($timezone) {
            $end = $end->setTimezone($timezone);
        }

        return $end->toDateString() < $start->toDateString();
    }

    protected static function parse(mixed $value): ?CarbonInterface
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
