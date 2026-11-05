<?php

namespace VentureDrake\LaravelCrm\Livewire\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use VentureDrake\LaravelCrm\Models\Person;

/**
 * The Guests picker on calls, meetings and lunches.
 *
 * It starts with nothing but the guests already attached and asks the server as
 * the user types, because three panels and every activity row each sending the
 * whole people list made a record page hydrate thousands of models.
 */
trait HasGuestSearch
{
    use SearchesEncryptableContacts;

    public const GUEST_SEARCH_LIMIT = 20;

    public array $guestOptions = [];

    /**
     * Whoever could open the form may search from it.
     */
    abstract protected function authorizeGuestSearch(): void;

    /**
     * Mary's picker drops a selected guest it has no option for, so the people
     * already chosen stay in the list alongside the matches.
     */
    public function searchGuests(string $value = ''): void
    {
        $this->authorizeGuestSearch();

        $this->guestOptions = $this->guestOptionsFor($this->guests)
            ->concat($this->matchingGuests(trim($value)))
            ->unique('id')
            ->values()
            ->all();
    }

    protected function loadSelectedGuests(): void
    {
        $this->guestOptions = $this->guestOptionsFor($this->guests)->all();
    }

    private function matchingGuests(string $term): Collection
    {
        if ($term === '') {
            return collect();
        }

        if ($this->encryptionEnabled()) {
            return $this->guestOptionsFor($this->matchingPersonIds($term)->take(self::GUEST_SEARCH_LIMIT)->all());
        }

        // Spaces match anything, so a first and last name find the full name.
        $like = '%'.str_replace(' ', '%', $term).'%';

        return Person::query()
            ->where(fn ($query) => $query
                ->where(DB::raw("CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))"), 'like', $like)
                ->orWhere('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like))
            ->orderBy('first_name')
            ->limit(self::GUEST_SEARCH_LIMIT)
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (Person $person) => ['id' => $person->id, 'name' => $person->name]);
    }

    private function guestOptionsFor(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        return Person::whereIn('id', $ids)
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (Person $person) => ['id' => $person->id, 'name' => $person->name])
            ->sortBy(fn (array $option) => strtolower($option['name']))
            ->values();
    }
}
