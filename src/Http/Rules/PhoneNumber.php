<?php

namespace VentureDrake\LaravelCrm\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A lenient phone number: digits, spaces and + - ( ) . with an optional "ext" or
 * "x" extension, and at least six digits before any extension. People write
 * numbers in too many shapes to be strict, so this only stops what can't be one.
 */
class PhoneNumber implements ValidationRule
{
    private const PATTERN = '/^([\d +\-().]+?) *(?:(?:ext\.?|x) *\d+)?\z/i';

    private const MINIMUM_DIGITS = 6;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->isValid($value)) {
            $fail(ucfirst(__('laravel-crm::lang.invalid_phone_number')));
        }
    }

    private function isValid(mixed $value): bool
    {
        return is_string($value)
            && preg_match(self::PATTERN, $value, $matches) === 1
            && preg_match_all('/\d/', $matches[1]) >= self::MINIMUM_DIGITS;
    }
}
