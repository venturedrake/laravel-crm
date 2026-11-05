<?php

namespace VentureDrake\LaravelCrm\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;

/**
 * A contact's email address, checked with `email:rfc` and never against DNS, so
 * a save doesn't depend on a lookup or on the address's domain being live.
 */
class ContactEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (Validator::make(['address' => $value], ['address' => 'email:rfc'])->fails()) {
            $fail(ucfirst(__('laravel-crm::lang.invalid_contact_email')));
        }
    }
}
