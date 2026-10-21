<?php

namespace VentureDrake\LaravelCrm\Support;

use VentureDrake\LaravelCrm\Models\XeroItem;

/**
 * The item code and description a CRM line sends to Xero.
 *
 * Variations are never pushed to Xero as items of their own. A line on a
 * variation uses the variation's code only when an item with that code has
 * already been synced from Xero; otherwise it uses the parent product's item,
 * as it always has. The description carries the variation name either way.
 *
 * No Dcblogdev reference lives here - callers already sit inside an
 * XeroIntegration::connected() guard.
 */
class XeroLineItem
{
    public static function itemCode($line): ?string
    {
        $variationCode = $line->product_variation_id ? $line->productVariation?->code : null;

        if ($variationCode && XeroItem::where('code', $variationCode)->exists()) {
            return $variationCode;
        }

        return $line->product?->xeroItem->code ?? $line->product?->code ?? null;
    }

    public static function description($line): string
    {
        return $line->displayName();
    }
}
