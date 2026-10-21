<?php

namespace VentureDrake\LaravelCrm\Traits;

use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\ProductVariation;

/**
 * Turns a submitted variation id into one that is safe to store on a line.
 *
 * The id arrives from a Livewire public property or an API payload, so it is
 * only kept when it names a variation of that same product in the current
 * team (the BelongsToTeams scope applies). A deleted variation is only kept
 * when the line already points at it - an existing line keeps its label, but
 * a new line cannot pick a deleted variation.
 */
trait ResolvesProductVariation
{
    protected function variationIdFor($productId, $variationId, $currentId = null): ?int
    {
        if (! $productId || ! $variationId) {
            return null;
        }

        $query = ProductVariation::query()
            ->where('product_id', $productId)
            ->whereKey($variationId);

        if ($currentId && (int) $currentId === (int) $variationId) {
            $query->withTrashed();
        }

        return $query->value('id');
    }

    /**
     * The variation on the line a new line is converted from (a deal line on
     * a quote, a quote line on an order, an order line on an invoice or
     * purchase order). Passed as $currentId so a conversion carries a variation
     * across even after it has been deleted from the product.
     *
     * @param  class-string<Model>  $lineClass
     */
    protected function sourceVariationId(string $lineClass, $lineId): ?int
    {
        return $lineId ? $lineClass::query()->whereKey($lineId)->value('product_variation_id') : null;
    }
}
