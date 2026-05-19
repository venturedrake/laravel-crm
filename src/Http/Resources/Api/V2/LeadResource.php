<?php

namespace VentureDrake\LaravelCrm\Http\Resources\Api\V2;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\Related\LabelBriefResource;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\Related\OrganizationBriefResource;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\Related\PersonBriefResource;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\Related\UserBriefResource;

class LeadResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->external_id,
            'lead_id' => $this->lead_id,
            'title' => $this->title,
            'description' => $this->description,
            'amount' => $this->amount !== null ? $this->amount / 100 : null,
            'currency' => $this->currency,
            'expected_close' => $this->expected_close
                ? Carbon::parse($this->expected_close)->toIso8601String()
                : null,
            'converted_at' => $this->converted_at
                ? Carbon::parse($this->converted_at)->toIso8601String()
                : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'owner' => $this->whenLoaded('ownerUser', fn () => $this->ownerUser ? new UserBriefResource($this->ownerUser) : null),
            'person' => $this->whenLoaded('person', fn () => $this->person ? new PersonBriefResource($this->person) : null),
            'organization' => $this->whenLoaded('organization', fn () => $this->organization ? new OrganizationBriefResource($this->organization) : null),
            'labels' => LabelBriefResource::collection($this->whenLoaded('labels')),
        ];
    }
}
