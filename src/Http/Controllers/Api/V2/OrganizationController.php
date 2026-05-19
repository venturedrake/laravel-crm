<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Api\V2;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\StoreOrganizationRequest;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\UpdateOrganizationRequest;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\OrganizationResource;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Services\OrganizationService;

class OrganizationController extends ApiController
{
    public function __construct(private OrganizationService $organizationService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Organization::class);

        $query = Organization::query()->with(['ownerUser', 'labels']);

        if ($request->filled('user_owner_id')) {
            $query->where('user_owner_id', $request->input('user_owner_id'));
        }

        $query = $this->applySort(
            $query,
            $request,
            ['created_at', 'updated_at', 'name'],
            '-created_at'
        );

        $organizations = $query->paginate($this->perPage($request))->withQueryString();

        return OrganizationResource::collection($organizations);
    }

    public function show(Organization $organization)
    {
        $this->authorize('view', $organization);

        $organization->load(['ownerUser', 'labels']);

        return new OrganizationResource($organization);
    }

    public function store(StoreOrganizationRequest $request)
    {
        $this->authorize('create', Organization::class);

        $payload = $this->buildPayload($request);

        $organization = $this->organizationService->create($payload);

        $organization->refresh()->load(['ownerUser', 'labels']);

        return (new OrganizationResource($organization))->response()->setStatusCode(201);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization)
    {
        $this->authorize('update', $organization);

        $payload = $this->buildPayload($request, $organization);

        $this->organizationService->update($organization, $payload);

        $organization->refresh()->load(['ownerUser', 'labels']);

        return new OrganizationResource($organization);
    }

    public function destroy(Organization $organization)
    {
        $this->authorize('delete', $organization);

        $organization->delete();

        return response()->noContent();
    }

    private function buildPayload(FormRequest $request, ?Organization $existing = null): object
    {
        return (object) [
            'name' => $request->input('name', $existing?->name),
            'description' => $request->input('description', $existing?->description),
            'vat_number' => $request->input('vat_number', $existing?->vat_number),
            'linkedin' => $request->input('linkedin', $existing?->linkedin),
            'number_of_employees' => $request->input('number_of_employees', $existing?->number_of_employees),
            'annual_revenue' => $request->input(
                'annual_revenue',
                $existing && $existing->annual_revenue !== null ? $existing->annual_revenue / 100 : null
            ),
            'total_money_raised' => $request->input(
                'total_money_raised',
                $existing && $existing->total_money_raised !== null ? $existing->total_money_raised / 100 : null
            ),
            'organization_type_id' => $request->input('organization_type_id', $existing?->organization_type_id),
            'industry_id' => $request->input('industry_id', $existing?->industry_id),
            'timezone_id' => $request->input('timezone_id', $existing?->timezone_id),
            'user_owner_id' => $request->input('user_owner_id', $existing?->user_owner_id),
            'phones' => null,
            'emails' => null,
            'addresses' => null,
        ];
    }
}
