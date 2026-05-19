<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Api\V2;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\StorePersonRequest;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\UpdatePersonRequest;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\PersonResource;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\PersonService;

class PersonController extends ApiController
{
    public function __construct(private PersonService $personService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Person::class);

        $query = Person::query()->with(['ownerUser', 'organization', 'labels']);

        if ($request->filled('user_owner_id')) {
            $query->where('user_owner_id', $request->input('user_owner_id'));
        }

        $query = $this->applySort(
            $query,
            $request,
            ['created_at', 'updated_at', 'first_name', 'last_name'],
            '-created_at'
        );

        $people = $query->paginate($this->perPage($request))->withQueryString();

        return PersonResource::collection($people);
    }

    public function show(Person $person)
    {
        $this->authorize('view', $person);

        $person->load(['ownerUser', 'organization', 'labels']);

        return new PersonResource($person);
    }

    public function store(StorePersonRequest $request)
    {
        $this->authorize('create', Person::class);

        $payload = $this->buildPayload($request);

        $person = $this->personService->create($payload);

        $this->applyDirectFields($person, $request);

        $person->refresh()->load(['ownerUser', 'organization', 'labels']);

        return (new PersonResource($person))->response()->setStatusCode(201);
    }

    public function update(UpdatePersonRequest $request, Person $person)
    {
        $this->authorize('update', $person);

        $payload = $this->buildPayload($request, $person);

        $this->personService->update($person, $payload);

        $this->applyDirectFields($person, $request);

        $person->refresh()->load(['ownerUser', 'organization', 'labels']);

        return new PersonResource($person);
    }

    public function destroy(Person $person)
    {
        $this->authorize('delete', $person);

        $person->delete();

        return response()->noContent();
    }

    private function buildPayload(FormRequest $request, ?Person $existing = null): object
    {
        return (object) [
            'title' => $request->input('title', $existing?->title),
            'first_name' => $request->input('first_name', $existing?->first_name),
            'middle_name' => $request->input('middle_name', $existing?->middle_name),
            'last_name' => $request->input('last_name', $existing?->last_name),
            'gender' => $request->input('gender', $existing?->gender),
            'birthday' => $request->input('birthday', $existing?->birthday?->toDateString()),
            'description' => $request->input('description', $existing?->description),
            'user_owner_id' => $request->input('user_owner_id', $existing?->user_owner_id),
            'phones' => null,
            'emails' => null,
            'addresses' => null,
        ];
    }

    private function applyDirectFields(Person $person, FormRequest $request): void
    {
        $touched = false;

        if ($request->has('organization_id')) {
            $organization = $request->filled('organization_id')
                ? Organization::where('external_id', $request->input('organization_id'))->first()
                : null;
            $person->organization_id = $organization?->id;
            $touched = true;
        }

        if ($touched) {
            $person->saveQuietly();
        }

        if ($request->has('labels')) {
            $labelIds = collect($request->input('labels', []))
                ->map(fn ($uuid) => Label::where('external_id', $uuid)->value('id'))
                ->filter()
                ->values()
                ->all();
            $person->labels()->sync($labelIds);
        }
    }
}
