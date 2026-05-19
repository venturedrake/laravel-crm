<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Api\V2;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\StoreDealRequest;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\UpdateDealRequest;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\DealResource;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrm\Services\DealService;

class DealController extends ApiController
{
    public function __construct(private DealService $dealService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Deal::class);

        $query = Deal::query()->with(['ownerUser', 'person', 'organization', 'lead', 'labels']);

        if ($request->filled('user_owner_id')) {
            $query->where('user_owner_id', $request->input('user_owner_id'));
        }

        $query = $this->applySort(
            $query,
            $request,
            ['created_at', 'updated_at', 'title', 'amount'],
            '-created_at'
        );

        $deals = $query->paginate($this->perPage($request))->withQueryString();

        return DealResource::collection($deals);
    }

    public function show(Deal $deal)
    {
        $this->authorize('view', $deal);

        $deal->load(['ownerUser', 'person', 'organization', 'lead', 'labels']);

        return new DealResource($deal);
    }

    public function store(StoreDealRequest $request)
    {
        $this->authorize('create', Deal::class);

        [$payload, $person, $organization] = $this->buildPayload($request);

        $deal = $this->dealService->create($payload, $person, $organization);

        $deal->refresh()->load(['ownerUser', 'person', 'organization', 'lead', 'labels']);

        return (new DealResource($deal))->response()->setStatusCode(201);
    }

    public function update(UpdateDealRequest $request, Deal $deal)
    {
        $this->authorize('update', $deal);

        [$payload, $person, $organization] = $this->buildPayload($request, $deal);

        $this->dealService->update($payload, $deal, $person, $organization);

        $deal->refresh()->load(['ownerUser', 'person', 'organization', 'lead', 'labels']);

        return new DealResource($deal);
    }

    public function destroy(Deal $deal)
    {
        $this->authorize('delete', $deal);

        $deal->delete();

        return response()->noContent();
    }

    private function buildPayload(FormRequest $request, ?Deal $existing = null): array
    {
        $person = $request->filled('person_id')
            ? Person::where('external_id', $request->input('person_id'))->first()
            : null;

        $organization = $request->filled('organization_id')
            ? Organization::where('external_id', $request->input('organization_id'))->first()
            : null;

        $lead = $request->filled('lead_id')
            ? Lead::where('external_id', $request->input('lead_id'))->first()
            : null;

        $pipelineStage = $request->filled('pipeline_stage_id')
            ? PipelineStage::where('external_id', $request->input('pipeline_stage_id'))->first()
            : null;

        $labelIds = collect($request->input('labels', []))
            ->map(fn ($uuid) => Label::where('external_id', $uuid)->value('id'))
            ->filter()
            ->values()
            ->all();

        $payload = (object) [
            'title' => $request->input('title', $existing?->title),
            'description' => $request->input('description', $existing?->description),
            'amount' => $request->input('amount', $existing ? ($existing->amount !== null ? $existing->amount / 100 : null) : null),
            'currency' => $request->input('currency', $existing?->currency) ?: 'USD',
            'expected_close' => $request->input('expected_close', $existing?->expected_close?->toIso8601String()),
            'lead_id' => $lead?->id ?? $existing?->lead_id,
            'user_owner_id' => $request->input('user_owner_id', $existing?->user_owner_id),
            'pipeline_stage_id' => $pipelineStage?->id ?? $existing?->pipeline_stage_id,
            'labels' => $labelIds,
        ];

        return [$payload, $person, $organization];
    }
}
