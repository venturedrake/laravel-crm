<?php

namespace VentureDrake\LaravelCrm\Http\Resources\Api\V2;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\Related\LabelBriefResource;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\Related\LeadBriefResource;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\Related\OrganizationBriefResource;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\Related\PersonBriefResource;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\Related\UserBriefResource;

class DealResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->external_id,
            'deal_id' => $this->deal_id,
            'title' => $this->title,
            'description' => $this->description,
            'amount' => $this->amount !== null ? $this->amount / 100 : null,
            'currency' => $this->currency,
            'expected_close' => $this->expected_close
                ? Carbon::parse($this->expected_close)->toIso8601String()
                : null,
            'closed_at' => $this->closed_at
                ? Carbon::parse($this->closed_at)->toIso8601String()
                : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'owner' => $this->whenLoaded('ownerUser', fn () => $this->ownerUser ? new UserBriefResource($this->ownerUser) : null),
            'person' => $this->whenLoaded('person', fn () => $this->person ? new PersonBriefResource($this->person) : null),
            'organization' => $this->whenLoaded('organization', fn () => $this->organization ? new OrganizationBriefResource($this->organization) : null),
            'lead' => $this->whenLoaded('lead', fn () => $this->lead ? new LeadBriefResource($this->lead) : null),
            'labels' => LabelBriefResource::collection($this->whenLoaded('labels')),
        ];
    }
}
