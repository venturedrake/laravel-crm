<?php

namespace VentureDrake\LaravelCrm\Http\Requests\Api\V2\Concerns;

use Illuminate\Validation\Validator;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductVariation;

/**
 * Rejects a line item whose `product_variation_id` is a variation of some
 * other product than the line's `product_id`. The `exists` rule alone only
 * proves the variation is in the caller's team.
 *
 * Both lookups go through the models, so the team scope applies. Deleted
 * variations are included: a line re-sent unchanged may point at a variation
 * deleted since it was added, and the service decides whether to keep it.
 */
trait ValidatesLineItemVariations
{
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $lineItems = $this->input('line_items');

                if (! is_array($lineItems)) {
                    return;
                }

                foreach ($lineItems as $index => $item) {
                    $variationKey = "line_items.{$index}.product_variation_id";

                    if (! is_array($item)
                        || empty($item['product_variation_id'])
                        || empty($item['product_id'])
                        || $validator->errors()->has($variationKey)
                        || $validator->errors()->has("line_items.{$index}.product_id")) {
                        continue;
                    }

                    $variationProductId = ProductVariation::withTrashed()
                        ->where('external_id', $item['product_variation_id'])
                        ->value('product_id');

                    $productId = Product::where('external_id', $item['product_id'])->value('id');

                    if ($variationProductId === null || $productId === null || (int) $variationProductId !== (int) $productId) {
                        $validator->errors()->add(
                            $variationKey,
                            "The selected {$variationKey} is not a variation of line_items.{$index}.product_id."
                        );
                    }
                }
            },
        ];
    }
}
