<?php

namespace VentureDrake\LaravelCrm\Traits;

/**
 * The name a line item prints: the product name, plus the variation name when
 * the line has one ("Widget — Large").
 *
 * Both relations are withTrashed() on the line models, so a line keeps its
 * label after the product or variation is deleted. Eager-load
 * `product` and `productVariation` wherever lines are listed.
 */
trait HasProductLineName
{
    public function displayName(): string
    {
        $name = (string) ($this->product?->name ?? '');

        $variationName = $this->product_variation_id ? (string) ($this->productVariation?->name ?? '') : '';

        if ($variationName === '') {
            return $name;
        }

        return $name === '' ? $variationName : $name.' — '.$variationName;
    }

    /**
     * The code a line prints: the variation's own code when it has one,
     * otherwise the product code.
     */
    public function displayCode(): ?string
    {
        if ($this->product_variation_id && ($code = $this->productVariation?->code)) {
            return $code;
        }

        return $this->product?->code;
    }
}
