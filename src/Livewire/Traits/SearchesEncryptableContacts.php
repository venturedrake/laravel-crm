<?php

namespace VentureDrake\LaravelCrm\Livewire\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;

/**
 * Helpers for v2 index components that need to search across the
 * encryptable Person and Organization name fields.
 *
 * When `laravel-crm.encrypt_db_fields` is OFF the database stores the
 * names in plaintext and SQL `LIKE` works directly. When it is ON the
 * stored values are ciphertext, so we have to fetch the rows, decrypt
 * via the `HasEncryptableFields` accessor, and return matching IDs that
 * the calling query can constrain on.
 *
 * Example:
 *
 *     ->orWhereIn('person_id', $this->matchingPersonIds($term))
 *     ->orWhereIn('organization_id', $this->matchingOrganizationIds($term))
 */
trait SearchesEncryptableContacts
{
    /**
     * Return Person IDs whose first_name / last_name / full name match
     * the search term. Returns an empty collection when no match.
     */
    protected function matchingPersonIds(?string $term): Collection
    {
        if ($term === null || trim($term) === '') {
            return collect();
        }

        $needle = strtolower($term);

        return Person::query()
            ->select(['id', 'first_name', 'last_name'])
            ->get()
            ->filter(function ($person) use ($needle) {
                return Str::contains(strtolower((string) $person->first_name), $needle)
                    || Str::contains(strtolower((string) $person->last_name), $needle)
                    || Str::contains(strtolower(trim($person->first_name.' '.$person->last_name)), $needle);
            })
            ->pluck('id');
    }

    /**
     * Return Organization IDs whose name matches the search term.
     * Returns an empty collection when no match.
     */
    protected function matchingOrganizationIds(?string $term): Collection
    {
        if ($term === null || trim($term) === '') {
            return collect();
        }

        $needle = strtolower($term);

        return Organization::query()
            ->select(['id', 'name'])
            ->get()
            ->filter(fn ($org) => Str::contains(strtolower((string) $org->name), $needle))
            ->pluck('id');
    }

    protected function encryptionEnabled(): bool
    {
        return (bool) config('laravel-crm.encrypt_db_fields');
    }
}
