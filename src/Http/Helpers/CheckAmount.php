<?php

namespace VentureDrake\LaravelCrm\Http\Helpers\CheckAmount;

use VentureDrake\LaravelCrm\Support\Quantity;

/**
 * Half a cent - the smallest amount the stored integer cents can tell apart.
 */
const TOLERANCE = 0.5;

/**
 * Does a computed money value agree with the one stored on the record?
 *
 * Prices are integer cents and quantities are decimal(15,3), so a line of
 * 3.5 at $9.99 computes 3496.5 cents against a stored 3497 - the computed
 * value is rounded to the cent the same way the stored one was before the
 * two are compared. An exact `==` would put a red mismatch icon on every
 * fractional line and a "broken document" badge on the index.
 */
function matches(float $computed, $stored): bool
{
    return abs(round($computed) - (float) $stored) < TOLERANCE;
}

/**
 * A line's value in cents, rounded the way the stored one was.
 *
 * The form rounds each line to the cent before summing them into the
 * subtotal, so the check has to round per line too. Summing the raw
 * products and rounding once at the end accumulates the half-cent
 * remainders - two lines of 0.5 at $9.99 store 500 + 500 = 1000 but
 * compute 499.5 + 499.5 = 999, and the document reads as broken.
 */
function lineTotal($item): float
{
    return round(Quantity::toFloat($item->quantity) * $item->price);
}

function subTotal($model): bool
{
    $total = 0;

    foreach (\VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\getItems($model) as $item) {
        $total += lineTotal($item);
    }

    return matches($total, $model->subtotal);
}

function tax($model): bool
{
    $total = 0;

    foreach (\VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\getItems($model) as $item) {
        $total += $item->tax_amount;
    }

    return matches($total, $model->tax);
}

function total($model): bool
{
    $total = 0;

    foreach (\VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\getItems($model) as $item) {
        $total += lineTotal($item);
    }

    $total = $total - $model->discount + $model->tax + $model->adjustments;

    return matches($total, $model->total);
}

function lineAmount($item): bool
{
    if ($item->price === null || $item->quantity === null || $item->amount === null) {
        return false;
    }

    return matches(lineTotal($item), $item->amount);
}

/**
 * The line items to check, off the eager-loaded relation where the caller
 * loaded one.
 *
 * subTotal() and total() are both called for every row of the quotes and orders
 * index pages, so querying here cost two round trips per row on top of whatever
 * else the row needed.
 */
function getItems($model)
{
    $relation = match (class_basename($model)) {
        'Quote' => 'quoteProducts',
        'Order' => 'orderProducts',
        default => null,
    };

    if ($relation === null) {
        return;
    }

    if ($model->relationLoaded($relation)) {
        return $model->getRelation($relation)->whereNotNull('product_id');
    }

    return $model->{$relation}()->whereNotNull('product_id')->get();
}
