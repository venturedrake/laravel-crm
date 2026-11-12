<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Api\V2;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\StorePositionRequest;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\UpdatePositionRequest;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\PositionResource;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Position;
use VentureDrake\LaravelCrm\Services\PositionService;

/**
 * Positions are read and written under the person or the organisation they
 * belong to. Both sub-resources share this logic; the concrete controllers
 * only name the parent in the URL.
 *
 * Every action needs `view` on the parent plus the matching PositionPolicy
 * ability, which is denied while the `positions` module is disabled. The
 * position is resolved through the parent (scoped binding), so a position of
 * another person or organisation is a 404.
 */
abstract class PositionController extends ApiController
{
    public function __construct(protected PositionService $positionService) {}

    /**
     * @param  Person|Organization  $parent
     */
    protected function listPositions(Request $request, $parent)
    {
        $this->authorize('view', $parent);
        $this->authorize('viewAny', Position::class);

        $positions = $parent->positions()
            ->with(['person', 'organization'])
            ->when($request->has('current'), fn ($query) => $request->boolean('current') ? $query->current() : $query->past())
            ->get();

        return PositionResource::collection($positions);
    }

    /**
     * @param  Person|Organization  $parent
     */
    protected function showPosition($parent, Position $position)
    {
        $this->authorize('view', $parent);
        $this->authorize('view', $position);

        return new PositionResource($position->load(['person', 'organization']));
    }

    /**
     * @param  Person|Organization  $parent
     */
    protected function storePosition(StorePositionRequest $request, $parent)
    {
        $this->authorize('view', $parent);
        $this->authorize('create', Position::class);

        [$personId, $organizationId] = $this->partyIds($request, $parent);

        $this->assertEndsAfterStart($request->input('start_date') ?: now(), $request->input('end_date'));

        $position = $this->positionService->create([
            'person_id' => $personId,
            'organization_id' => $organizationId,
            'title' => $request->input('title'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'description' => $request->input('description'),
        ], $request->boolean('end_conflicting', true) ? null : []);

        return (new PositionResource($position->refresh()->load(['person', 'organization'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @param  Person|Organization  $parent
     */
    protected function updatePosition(UpdatePositionRequest $request, $parent, Position $position)
    {
        $this->authorize('view', $parent);
        $this->authorize('update', $position);

        // Only the keys the request sent, so an update leaves the rest alone.
        $data = [];

        foreach (['title', 'start_date', 'end_date', 'description'] as $key) {
            if ($request->exists($key)) {
                $data[$key] = $request->input($key);
            }
        }

        $this->assertEndsAfterStart(
            $data['start_date'] ?? $position->start_date,
            array_key_exists('end_date', $data) ? $data['end_date'] : $position->end_date
        );

        $position = $this->positionService->update($position, $data);

        return new PositionResource($position->refresh()->load(['person', 'organization']));
    }

    /**
     * @param  Person|Organization  $parent
     */
    protected function destroyPosition($parent, Position $position)
    {
        $this->authorize('view', $parent);
        $this->authorize('delete', $position);

        $this->positionService->delete($position);

        return response()->noContent();
    }

    /**
     * The person and organisation of a new position: the parent in the URL
     * is one, the request body names the other. Under a person the
     * organisation defaults to the person's own.
     *
     * @param  Person|Organization  $parent
     * @return array{0: int, 1: ?int}
     */
    private function partyIds(StorePositionRequest $request, $parent): array
    {
        if ($parent instanceof Organization) {
            return [Person::where('external_id', $request->input('person_id'))->firstOrFail()->id, $parent->id];
        }

        $organizationId = $request->filled('organization_id')
            ? Organization::where('external_id', $request->input('organization_id'))->firstOrFail()->id
            : $parent->organization_id;

        return [$parent->id, $organizationId];
    }

    /**
     * Checked against the dates the position will end up with, so a request
     * that sends only one of the two is still held to the other.
     */
    private function assertEndsAfterStart($start, $end): void
    {
        if ($start && $end && Carbon::parse($end)->startOfDay()->lt(Carbon::parse($start)->startOfDay())) {
            throw ValidationException::withMessages([
                'end_date' => [__('validation.after_or_equal', ['attribute' => 'end date', 'date' => 'start date'])],
            ]);
        }
    }
}
