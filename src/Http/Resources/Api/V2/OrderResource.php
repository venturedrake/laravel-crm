<?php

namespace VentureDrake\LaravelCrm\Http\Resources\Api\V2;

use Illuminate\Http\Resources\Json\JsonResource;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\Related\LabelBriefResource;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\Related\OrganizationBriefResource;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\Related\PersonBriefResource;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\Related\UserBriefResource;

class OrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->external_id,
            'order_id' => $this->order_id,
            'reference' => $this->reference,
            'description' => $this->description,
            'currency' => $this->currency,
            'terms' => $this->terms,
            'subtotal' => $this->subtotal !== null ? $this->subtotal / 100 : null,
            'discount' => $this->discount !== null ? $this->discount / 100 : null,
            'tax' => $this->tax !== null ? $this->tax / 100 : null,
            'adjustments' => $this->adjustments !== null ? $this->adjustments / 100 : null,
            'total' => $this->total !== null ? $this->total / 100 : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'owner' => $this->whenLoaded('ownerUser', fn () => $this->ownerUser ? new UserBriefResource($this->ownerUser) : null),
            'person' => $this->whenLoaded('person', fn () => $this->person ? new PersonBriefResource($this->person) : null),
            'organization' => $this->whenLoaded('organization', fn () => $this->organization ? new OrganizationBriefResource($this->organization) : null),
            'labels' => LabelBriefResource::collection($this->whenLoaded('labels')),
            'line_items' => OrderProductResource::collection($this->whenLoaded('orderProducts')),
        ];
    }
}
