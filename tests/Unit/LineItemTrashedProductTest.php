<?php

use VentureDrake\LaravelCrm\Models\InvoiceLine;
use VentureDrake\LaravelCrm\Models\OrderProduct;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\QuoteProduct;

/*
 * Deleting a product from the CRM soft-deletes it but leaves historic document lines
 * pointing at it. Without withTrashed() on the relation the line's product resolves to
 * null and every quote/order/invoice show view fatals on ->name, so the document becomes
 * permanently unviewable. A financial document must keep showing what was actually sold.
 */

dataset('line item models', [
    'quote product' => [QuoteProduct::class, ['quote_id' => 1]],
    'order product' => [OrderProduct::class, ['order_id' => 1]],
    'invoice line' => [InvoiceLine::class, ['invoice_id' => 1]],
]);

test('line item still resolves a soft deleted product', function (string $model, array $parent) {
    $product = Product::create(['name' => 'Retired Widget', 'code' => 'RW-1']);
    $line = $model::create($parent + ['product_id' => $product->id, 'quantity' => 1]);

    $product->delete();

    $line = $model::find($line->id);

    expect($line->product)->not->toBeNull()
        ->and($line->product->name)->toBe('Retired Widget')
        ->and($line->product->code)->toBe('RW-1')
        ->and($line->product->trashed())->toBeTrue();
})->with('line item models');

test('line item pointing at a missing product resolves to null', function (string $model, array $parent) {
    $line = $model::create($parent + ['product_id' => 999999, 'quantity' => 1]);

    expect($model::find($line->id)->product)->toBeNull();
})->with('line item models');
