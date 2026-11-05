<?php

namespace VentureDrake\LaravelCrm\Http\Requests\Api\V2\Concerns;

use VentureDrake\LaravelCrm\Http\Rules\ContactEmail;
use VentureDrake\LaravelCrm\Http\Rules\PhoneNumber;

/**
 * The format rules the web forms apply to a contact's email and phone, in both
 * the single-field and the list shape those forms use.
 */
trait ValidatesContactDetails
{
    protected function contactDetailRules(): array
    {
        return [
            'email' => ['nullable', new ContactEmail],
            'phone' => ['nullable', new PhoneNumber],
            'emails' => ['nullable', 'array'],
            'emails.*.address' => ['nullable', new ContactEmail],
            'phones' => ['nullable', 'array'],
            'phones.*.number' => ['nullable', new PhoneNumber],
        ];
    }
}
