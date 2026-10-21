<?php

namespace VentureDrake\LaravelCrm\Livewire\Traits;

use VentureDrake\LaravelCrm\Models\ProductVariation;

/**
 * The variation select on a line item row - shared by crm-model-products and
 * the deal form.
 *
 * A product with no active variations has no options, so the row renders
 * exactly as it did before variations existed.
 */
trait HasVariationOptions
{
    /**
     * Per-request memo, keyed by product id. Protected so it is not
     * serialised into the Livewire snapshot.
     *
     * @var array<int, array<int, array{id: int, name: string}>>
     */
    protected array $variationOptionsCache = [];

    /**
     * The active variations of a product, in order, as select options.
     *
     * A line already on a variation that has since been deactivated or
     * deleted keeps it in its own options, so the select still shows what the
     * line holds. Team scoping applies through the model's global scope.
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function variationOptions($productId, $currentVariationId = null): array
    {
        if (! is_numeric($productId)) {
            return [];
        }

        $productId = (int) $productId;

        if (! array_key_exists($productId, $this->variationOptionsCache)) {
            $this->variationOptionsCache[$productId] = ProductVariation::query()
                ->where('product_id', $productId)
                ->active()
                ->ordered()
                ->get()
                ->map(fn (ProductVariation $variation) => $this->variationOption($variation))
                ->all();
        }

        $options = $this->variationOptionsCache[$productId];

        if (is_numeric($currentVariationId) && ! collect($options)->contains('id', (int) $currentVariationId)) {
            $current = ProductVariation::withTrashed()
                ->where('product_id', $productId)
                ->whereKey($currentVariationId)
                ->first();

            if ($current) {
                $options[] = $this->variationOption($current);
            }
        }

        return $options;
    }

    /**
     * Resolve a submitted variation id against the row's product. Null when
     * blank, or when it names a variation of another product or team.
     *
     * Deleted variations still resolve, so a line already on one keeps it
     * (and its price) while other fields are edited - the select only ever
     * offers a deleted variation to the line that already holds it, and the
     * services drop a deleted variation that was not already on the line.
     */
    protected function resolveRowVariation($productId, $variationId): ?ProductVariation
    {
        if (! is_numeric($productId) || ! is_numeric($variationId)) {
            return null;
        }

        return ProductVariation::withTrashed()
            ->where('product_id', $productId)
            ->whereKey($variationId)
            ->first();
    }

    /**
     * @return array{id: int, name: string}
     */
    protected function variationOption(ProductVariation $variation): array
    {
        return [
            'id' => $variation->id,
            'name' => $variation->code ? $variation->name.' ('.$variation->code.')' : $variation->name,
        ];
    }
}
