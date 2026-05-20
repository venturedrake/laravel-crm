<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Api\V2;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\StoreLeadRequest;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\UpdateLeadRequest;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\LeadResource;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\LeadSource;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrm\Services\LeadService;

class LeadController extends ApiController
{
    public function __construct(private LeadService $leadService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Lead::class);

        $query = Lead::query()->with(['ownerUser', 'person', 'organization', 'labels']);

        if ($request->filled('user_owner_id')) {
            $query->where('user_owner_id', $request->input('user_owner_id'));
        }

        $query = $this->applySort(
            $query,
            $request,
            ['created_at', 'updated_at', 'title', 'amount'],
            '-created_at'
        );

        $leads = $query->paginate($this->perPage($request))->withQueryString();

        return LeadResource::collection($leads);
    }

    public function show(Lead $lead)
    {
        $this->authorize('view', $lead);

        $lead->load(['ownerUser', 'person', 'organization', 'labels']);

        return new LeadResource($lead);
    }

    public function store(StoreLeadRequest $request)
    {
        $this->authorize('create', Lead::class);

        [$payload, $person, $organization] = $this->buildPayload($request);

        $lead = $this->leadService->create($payload, $person, $organization);

        $this->applyDirectFields($lead, $request);

        $lead->refresh()->load(['ownerUser', 'person', 'organization', 'labels']);

        return (new LeadResource($lead))->response()->setStatusCode(201);
    }

    public function update(UpdateLeadRequest $request, Lead $lead)
    {
        $this->authorize('update', $lead);

        [$payload, $person, $organization] = $this->buildPayload($request, $lead);

        $this->leadService->update($payload, $lead, $person, $organization);

        $this->applyDirectFields($lead, $request);

        $lead->refresh()->load(['ownerUser', 'person', 'organization', 'labels']);

        return new LeadResource($lead);
    }

    public function destroy(Lead $lead)
    {
        $this->authorize('delete', $lead);

        $lead->delete();

        return response()->noContent();
    }

    private function buildPayload(FormRequest $request, ?Lead $existing = null): array
    {
        if ($request->filled('person_id')) {
            $person = Person::where('external_id', $request->input('person_id'))->first();
        } elseif ($request->has('person_id')) {
            $person = null;
        } else {
            $person = $existing?->person;
        }

        if ($request->filled('organization_id')) {
            $organization = Organization::where('external_id', $request->input('organization_id'))->first();
        } elseif ($request->has('organization_id')) {
            $organization = null;
        } else {
            $organization = $existing?->organization;
        }

        $leadSource = $request->filled('lead_source_id')
            ? LeadSource::where('external_id', $request->input('lead_source_id'))->first()
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
            'lead_source_id' => $leadSource?->id ?? $existing?->lead_source_id,
            'user_owner_id' => $request->input('user_owner_id', $existing?->user_owner_id),
            'pipeline_stage_id' => $pipelineStage?->id ?? $existing?->pipeline_stage_id,
            'labels' => $labelIds,
        ];

        return [$payload, $person, $organization];
    }

    private function applyDirectFields(Lead $lead, FormRequest $request): void
    {
        if ($request->filled('expected_close')) {
            $lead->expected_close = $request->input('expected_close');
            $lead->saveQuietly();
        }
    }
}
