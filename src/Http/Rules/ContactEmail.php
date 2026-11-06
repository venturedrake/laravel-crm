<?php

namespace VentureDrake\LaravelCrm\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;

/**
 * A contact's email address, checked with `email:rfc` and never against DNS, so
 * a save doesn't depend on a lookup or on the address's domain being live.
 *
 * Addresses in $saved are already on the record and pass as they are, so one that
 * predates this rule or came from a sync doesn't block an unrelated edit.
 */
class ContactEmail implements ValidationRule
{
    public function __construct(private array $saved = []) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (in_array($value, $this->saved, true)) {
            return;
        }

        if (Validator::make(['address' => $value], ['address' => 'email:rfc'])->fails()) {
            $fail(ucfirst(__('laravel-crm::lang.invalid_contact_email')));
        }
    }
}
