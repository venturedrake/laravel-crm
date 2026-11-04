<?php

namespace VentureDrake\LaravelCrm\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;

/**
 * Accept one or more email addresses separated by commas or semicolons, and
 * reject the field if any of them is not a valid address.
 *
 * The Send dialogs used to validate the recipient as `required|string`, so
 * `not-an-email` got through, the PDF was rendered and saved, and then
 * Symfony Mime threw an RfcComplianceException when the mailable set its
 * To header - a 500 instead of a validation message. A plain `email` rule
 * would have fixed that but refused the comma-separated list people type
 * into a To field, which the mailables already accept as an array.
 */
class EmailAddressList implements ValidationRule
{
    /**
     * Split a recipient field into trimmed addresses, dropping empty entries
     * left by stray or trailing separators.
     *
     * @return list<string>
     */
    public static function parse(?string $value): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/[,;]/', (string) $value)),
            fn (string $address) => $address !== '',
        ));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $addresses = is_string($value) ? static::parse($value) : [];

        if ($addresses === []) {
            $fail('validation.required')->translate();

            return;
        }

        foreach ($addresses as $address) {
            if (Validator::make(['address' => $address], ['address' => 'email:rfc'])->fails()) {
                // No ucfirst(): the message leads with the address exactly as typed.
                $fail(__('laravel-crm::lang.invalid_email_address', ['address' => $address]));

                return;
            }
        }
    }
}
