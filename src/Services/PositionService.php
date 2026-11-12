<?php

namespace VentureDrake\LaravelCrm\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Position;

class PositionService
{
    /**
     * The current positions that adding a new one would bring to an end:
     *
     *  - the person's own current positions at the organisation (they changed
     *    position), and
     *  - current positions at the organisation with the same title held by
     *    someone else (the position changed hands).
     *
     * Titles match when equal after trimming and ignoring case.
     *
     * @return Collection<int, Position>
     */
    public function conflicts(?int $personId, ?int $organizationId, ?string $title, ?int $ignoreId = null): Collection
    {
        $title = trim((string) $title);

        if (! $organizationId || (! $personId && $title === '')) {
            return collect();
        }

        return Position::with(['person', 'organization'])
            ->current()
            ->where('organization_id', $organizationId)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where(function ($query) use ($personId, $title) {
                if ($personId) {
                    $query->orWhere('person_id', $personId);
                }

                if ($title !== '') {
                    $query->orWhere(fn ($query) => $query->titled($title));
                }
            })
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Create a position and end the ones it replaces. A current position
     * also links the person and the organisation (see linkOrganization()).
     *
     * `$end` is the list of position ids to end, out of those conflicts()
     * returns; anything else in the list is ignored. Null ends every conflict.
     * Each ended position takes the new position's start date as its end date.
     *
     * @param  array{person_id:int, organization_id:int, title:string, start_date?:mixed, end_date?:mixed, description?:?string}  $data
     * @param  array<int, int>|null  $end
     */
    public function create(array $data, ?array $end = null): Position
    {
        return DB::transaction(function () use ($data, $end) {
            $startDate = $this->date($data['start_date'] ?? null) ?? now()->startOfDay();

            $conflicts = $this->conflicts($data['person_id'], $data['organization_id'], $data['title']);

            if ($end !== null) {
                $conflicts = $conflicts->whereIn('id', array_map('intval', $end));
            }

            foreach ($conflicts as $conflict) {
                $this->end($conflict, $startDate);
            }

            $position = Position::create([
                'person_id' => $data['person_id'],
                'organization_id' => $data['organization_id'],
                'title' => $data['title'],
                'start_date' => $startDate->toDateString(),
                'end_date' => $this->date($data['end_date'] ?? null)?->toDateString(),
                'description' => $data['description'] ?? null,
            ]);

            $this->linkOrganization($position);

            return $position;
        });
    }

    /**
     * A current position links its person and organisation, the same way the
     * Related organisations and Related people panels do: a contact record in
     * each direction, added only when it is not there already.
     *
     * It also becomes the person's organisation when the person has none. A
     * person who already belongs to another organisation keeps it: the
     * position is one they hold elsewhere (a board seat, say), and moving
     * them would end their positions there.
     *
     * Ending or deleting a position removes none of these links.
     */
    protected function linkOrganization(Position $position): void
    {
        $person = $position->person;
        $organization = $position->organization;

        if (! $person || ! $organization || $person->trashed() || $organization->trashed() || ! $position->isCurrent()) {
            return;
        }

        $person->contacts()->firstOrCreate([
            'entityable_type' => $organization->getMorphClass(),
            'entityable_id' => $organization->id,
        ]);

        $organization->contacts()->firstOrCreate([
            'entityable_type' => $person->getMorphClass(),
            'entityable_id' => $person->id,
        ]);

        if (! $person->organization_id) {
            $person->organization_id = $organization->id;
            $person->save();
        }
    }

    /**
     * @param  array{title?:string, start_date?:mixed, end_date?:mixed, description?:?string}  $data
     */
    public function update(Position $position, array $data): Position
    {
        $position->fill(Arr::only($data, ['title', 'description']));

        // A start date is never cleared; an end date is, by sending null.
        if ($startDate = $this->date($data['start_date'] ?? null)) {
            $position->start_date = $startDate->toDateString();
        }

        if (array_key_exists('end_date', $data)) {
            $position->end_date = $this->date($data['end_date'])?->toDateString();
        }

        $position->save();

        return $position;
    }

    /**
     * End a position on `$date` (today by default). A position never ends
     * before it started: an earlier date is moved up to the start date.
     */
    public function end(Position $position, $date = null): Position
    {
        $date = $this->date($date) ?? now()->startOfDay();

        if ($position->start_date && $date->lt($position->start_date)) {
            $date = $position->start_date->copy();
        }

        $position->end_date = $date->toDateString();
        $position->save();

        return $position;
    }

    public function delete(Position $position): void
    {
        $position->delete();
    }

    /**
     * End a person's current positions, at one organisation or at all of them.
     */
    public function endForPerson(int $personId, ?int $organizationId = null, $date = null): int
    {
        $positions = Position::current()
            ->where('person_id', $personId)
            ->when($organizationId, fn ($query) => $query->where('organization_id', $organizationId))
            ->get();

        $positions->each(fn (Position $position) => $this->end($position, $date));

        return $positions->count();
    }

    /**
     * Delete every position, past and current, of a person or at an
     * organisation that has been deleted, so none is left pointing at a
     * record that is gone. Soft-deleted like its owner, or removed for good
     * when the owner is.
     *
     * @param  Person|Organization  $owner
     */
    public function deleteFor($owner, bool $force = false): int
    {
        $positions = $owner->positions()->when($force, fn ($query) => $query->withTrashed())->get();

        // One at a time, so each delete runs the position observer.
        $positions->each(fn (Position $position) => $force ? $position->forceDelete() : $position->delete());

        return $positions->count();
    }

    /**
     * Titles already in use, most used first, for the title field's suggestions.
     *
     * @return Collection<int, string>
     */
    public function titleSuggestions(?string $term = null, int $limit = 50): Collection
    {
        return Position::query()
            ->select('title', DB::raw('COUNT(*) as uses'))
            ->when(trim((string) $term) !== '', fn ($query) => $query->where('title', 'like', '%'.trim($term).'%'))
            ->groupBy('title')
            ->orderByDesc('uses')
            ->orderBy('title')
            ->limit($limit)
            ->pluck('title');
    }

    protected function date($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->startOfDay();
    }
}
