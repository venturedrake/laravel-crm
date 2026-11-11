<?php

namespace VentureDrake\LaravelCrm\Support;

use VentureDrake\LaravelCrm\Models\Address;

/**
 * Whether an address says anything. The order and delivery forms prefill the
 * country from settings, so an address left untouched still arrives with a
 * country and a type - and used to be saved as one that was only a country.
 *
 * Works on a request array or an Address model; a model is read through its
 * attributes, so encrypted fields are decrypted first.
 */
class AddressContent
{
    /**
     * The fields someone types. Country, type, id and primary are set by the
     * form, not the user. The `address` label is left out too: the edit forms
     * fill it from the accessor, which for an empty address is just
     * separators and the country.
     */
    const FIELDS = ['line1', 'line2', 'line3', 'city', 'state', 'code', 'contact', 'phone', 'name'];

    public static function isBlank(array|Address $address): bool
    {
        foreach (self::FIELDS as $field) {
            if (self::filled($address, $field)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Enough to deliver to: a street or a city.
     */
    public static function isDeliverable(array|Address $address): bool
    {
        return self::filled($address, 'line1') || self::filled($address, 'city');
    }

    protected static function filled(array|Address $address, string $field): bool
    {
        $value = $address instanceof Address
            ? $address->getAttribute($field)
            : ($address[$field] ?? null);

        return trim((string) $value) !== '';
    }
}
