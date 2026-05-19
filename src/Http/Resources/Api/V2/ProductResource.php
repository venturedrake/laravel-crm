<?php

namespace VentureDrake\LaravelCrm\Http\Resources\Api\V2;

use Illuminate\Http\Resources\Json\JsonResource;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\Related\UserBriefResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->external_id,
            'name' => $this->name,
            'code' => $this->code,
            'barcode' => $this->barcode,
            'description' => $this->description,
            'unit' => $this->unit,
            'tax_rate' => $this->tax_rate !== null ? (float) $this->tax_rate : null,
            'tax_rate_id' => $this->tax_rate_id,
            'product_category_id' => $this->whenLoaded('productCategory', fn () => $this->productCategory?->external_id),
            'purchase_account' => $this->purchase_account,
            'sales_account' => $this->sales_account,
            'active' => (bool) $this->active,
            'prices' => $this->whenLoaded('productPrices', fn () => $this->productPrices->map(fn ($price) => [
                'currency' => $price->currency,
                'unit_price' => $price->unit_price !== null ? $price->unit_price / 100 : null,
                'default' => (bool) $price->default,
            ])->values()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'owner' => $this->whenLoaded('ownerUser', fn () => $this->ownerUser ? new UserBriefResource($this->ownerUser) : null),
        ];
    }
}
