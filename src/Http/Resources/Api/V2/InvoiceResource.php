<?php

namespace VentureDrake\LaravelCrm\Http\Resources\Api\V2;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\Related\LabelBriefResource;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\Related\OrganizationBriefResource;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\Related\PersonBriefResource;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\Related\UserBriefResource;

class InvoiceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->external_id,
            'invoice_id' => $this->invoice_id,
            'reference' => $this->reference,
            'issue_date' => $this->issue_date ? Carbon::parse($this->issue_date)->toIso8601String() : null,
            'due_date' => $this->due_date ? Carbon::parse($this->due_date)->toIso8601String() : null,
            'currency' => $this->currency,
            'terms' => $this->terms,
            'subtotal' => $this->subtotal !== null ? $this->subtotal / 100 : null,
            'tax' => $this->tax !== null ? $this->tax / 100 : null,
            'total' => $this->total !== null ? $this->total / 100 : null,
            'amount_due' => $this->amount_due !== null ? $this->amount_due / 100 : null,
            'amount_paid' => $this->amount_paid !== null ? $this->amount_paid / 100 : null,
            'fully_paid_at' => $this->fully_paid_at ? Carbon::parse($this->fully_paid_at)->toIso8601String() : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'owner' => $this->whenLoaded('ownerUser', fn () => $this->ownerUser ? new UserBriefResource($this->ownerUser) : null),
            'person' => $this->whenLoaded('person', fn () => $this->person ? new PersonBriefResource($this->person) : null),
            'organization' => $this->whenLoaded('organization', fn () => $this->organization ? new OrganizationBriefResource($this->organization) : null),
            'labels' => LabelBriefResource::collection($this->whenLoaded('labels')),
            'line_items' => InvoiceLineResource::collection($this->whenLoaded('invoiceLines')),
        ];
    }
}
