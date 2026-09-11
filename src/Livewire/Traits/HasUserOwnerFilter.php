<?php

namespace VentureDrake\LaravelCrm\Livewire\Traits;

use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * Server-side search for the Owner filter shared by the index and board screens.
 *
 * MaryUI json_encodes every option into an Alpine x-data block and renders a
 * <div> per option, and Livewire then serialises and checksums that payload on
 * every round trip. Handing it the whole users table made a 25-row page cost
 * tens of megabytes on installs with thousands of users, so the options are
 * searched and capped here instead.
 *
 * Expects the using component to declare `public ?array $user_id`.
 */
trait HasUserOwnerFilter
{
    public string $userSearch = '';

    #[Computed]
    public function users(): Collection
    {
        $matches = User::query()
            ->select('id', 'name')
            ->when($this->userSearch !== '', fn ($q) => $q->where('name', 'like', '%'.$this->userSearch.'%'))
            ->orderBy('name')
            ->limit(20)
            ->get();

        // MaryUI reads a selected chip's label out of the options collection it
        // was handed, so an owner that falls out of the matches renders blank.
        $selected = filled($this->user_id)
            ? User::query()->select('id', 'name')->whereIn('id', $this->user_id)->get()
            : new Collection;

        return $selected->merge($matches)->unique('id')->sortBy('name')->values();
    }

    public function searchUsers(string $value = ''): void
    {
        $this->userSearch = $value;

        unset($this->users);
    }
}
