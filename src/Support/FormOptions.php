<?php

namespace VentureDrake\LaravelCrm\Support;

use VentureDrake\LaravelCrm\Models\AddressType;

/**
 * Install-wide field toggles for a document form, read from
 * config("laravel-crm.{$documentType}.form").
 *
 * Every key is optional and defaults to the full form, so a host that has
 * published an older config (mergeConfigFrom only merges top-level keys) or
 * sets only some of them gets today's behaviour for the rest.
 *
 * Only `orders` reads this today. Quotes, invoices and purchase orders can
 * take the same shape under their own key.
 */
class FormOptions
{
    /**
     * The address tabs a document form knows how to render, keyed by the
     * config name and mapped to the AddressType row name they save as.
     */
    public const ADDRESS_TYPES = [
        'billing' => 'Billing',
        'shipping' => 'Shipping',
    ];

    public function __construct(protected string $documentType) {}

    public static function for(string $documentType): self
    {
        return new self($documentType);
    }

    /**
     * Whether a toggleable field (description, labels, custom_fields) is shown.
     */
    public function shows(string $field): bool
    {
        return (bool) $this->get($field, true);
    }

    public function allowsUnallocatedOwner(): bool
    {
        return (bool) $this->get('unallocated_owner', true);
    }

    /**
     * The address tabs to show and save, in the configured order, as
     * ADDRESS_TYPES keys. Unknown names are ignored.
     *
     * @return list<string>
     */
    public function addressTypes(): array
    {
        $configured = $this->get('addresses', array_keys(self::ADDRESS_TYPES));

        if (! is_array($configured)) {
            return array_keys(self::ADDRESS_TYPES);
        }

        $types = [];

        foreach ($configured as $name) {
            $key = strtolower(trim((string) $name));

            if (isset(self::ADDRESS_TYPES[$key]) && ! in_array($key, $types, true)) {
                $types[] = $key;
            }
        }

        return $types;
    }

    public function showsAddressType(string $type): bool
    {
        return in_array($type, $this->addressTypes(), true);
    }

    /**
     * Ids of the AddressType rows the form knows but is configured not to
     * show. Addresses of these types are left alone on save.
     *
     * @return list<int>
     */
    public function hiddenAddressTypeIds(): array
    {
        $hidden = array_diff_key(self::ADDRESS_TYPES, array_flip($this->addressTypes()));

        if (! $hidden) {
            return [];
        }

        return AddressType::whereIn('name', array_values($hidden))->pluck('id')->all();
    }

    protected function get(string $key, mixed $default): mixed
    {
        return config("laravel-crm.{$this->documentType}.form.{$key}", $default);
    }
}
