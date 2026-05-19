<?php

namespace VentureDrake\LaravelCrm\Http\Resources\Api\V2;

use Illuminate\Http\Resources\Json\JsonResource;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\Related\LabelBriefResource;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\Related\UserBriefResource;

class OrganizationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->external_id,
            'name' => $this->name,
            'description' => $this->description,
            'vat_number' => $this->vat_number,
            'linkedin' => $this->linkedin,
            'number_of_employees' => $this->number_of_employees !== null ? (int) $this->number_of_employees : null,
            'annual_revenue' => $this->annual_revenue !== null ? $this->annual_revenue / 100 : null,
            'total_money_raised' => $this->total_money_raised !== null ? $this->total_money_raised / 100 : null,
            'organization_type_id' => $this->organization_type_id,
            'industry_id' => $this->industry_id,
            'timezone_id' => $this->timezone_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'owner' => $this->whenLoaded('ownerUser', fn () => $this->ownerUser ? new UserBriefResource($this->ownerUser) : null),
            'labels' => LabelBriefResource::collection($this->whenLoaded('labels')),
        ];
    }
}
