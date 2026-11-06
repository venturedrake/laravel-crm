<?php

namespace VentureDrake\LaravelCrm\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A lenient phone number: digits, spaces and + - ( ) . with an optional "ext" or
 * "x" extension, and at least six digits before any extension. People write
 * numbers in too many shapes to be strict, so this only stops what can't be one.
 *
 * The possessive `++` is deliberate: nothing limits the length of the value, and
 * a backtracking group takes seconds to refuse a long one.
 *
 * Numbers in $saved are already on the record and pass as they are, so one that
 * predates this rule or came from an import doesn't block an unrelated edit.
 */
class PhoneNumber implements ValidationRule
{
    private const PATTERN = '/^([\d +\-().]++)(?:(?:ext\.?|x) *\d+)?\z/i';

    private const MINIMUM_DIGITS = 6;

    public function __construct(private array $saved = []) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! in_array($value, $this->saved, true) && ! $this->isValid($value)) {
            $fail(ucfirst(__('laravel-crm::lang.invalid_phone_number')));
        }
    }

    private function isValid(mixed $value): bool
    {
        return (is_string($value) || is_int($value))
            && preg_match(self::PATTERN, (string) $value, $matches) === 1
            && preg_match_all('/\d/', $matches[1]) >= self::MINIMUM_DIGITS;
    }
}
