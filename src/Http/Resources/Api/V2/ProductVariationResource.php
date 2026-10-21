<?php

namespace VentureDrake\LaravelCrm\Http\Resources\Api\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->external_id,
            'name' => $this->name,
            'code' => $this->code,
            'barcode' => $this->barcode,
            'active' => (bool) $this->active,
            'order' => $this->order !== null ? (int) $this->order : null,
            'prices' => $this->whenLoaded('productPrices', fn () => $this->productPrices->map(fn ($price) => [
                'currency' => $price->currency,
                'unit_price' => $price->unit_price !== null ? $price->unit_price / 100 : null,
            ])->values()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
