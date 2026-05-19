<?php

namespace VentureDrake\LaravelCrm\Http\Resources\Api\V2;

use Illuminate\Http\Resources\Json\JsonResource;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\Related\ProductBriefResource;

class OrderProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->external_id,
            'product_id' => $this->whenLoaded('product', fn () => $this->product?->external_id),
            'product' => $this->whenLoaded('product', fn () => $this->product ? new ProductBriefResource($this->product) : null),
            'quantity' => $this->quantity !== null ? (int) $this->quantity : null,
            'unit_price' => $this->price !== null ? $this->price / 100 : null,
            'amount' => $this->amount !== null ? $this->amount / 100 : null,
            'tax_rate' => $this->tax_rate !== null ? (float) $this->tax_rate : null,
            'tax_amount' => $this->tax_amount !== null ? $this->tax_amount / 100 : null,
            'currency' => $this->currency,
            'comments' => $this->comments,
            'order' => $this->order !== null ? (int) $this->order : null,
        ];
    }
}
