<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Support\PdfSampleData;
use VentureDrake\LaravelCrm\Support\PdfTemplateRegistry;

/*
 * The delivery blades walk two relations to print a line — the delivery product's
 * order product, and that order product's product — and either can come back null:
 * `crm_delivery_products.order_product_id` is nullable, and a product can be deleted
 * long after the delivery was raised. A null read on either is an ErrorException under
 * Laravel's error handler, so the whole document 500s rather than dropping a cell.
 *
 * Rendered through View::make rather than DomPDF: the assertion wants readable HTML,
 * and the DomPDF path is already covered by PdfTemplateRenderingTest. Every slug is
 * exercised because the five delivery blades carry their own copy of the line markup.
 */

beforeEach(function () {
    if (! Schema::hasTable('crm_delivery_products')) {
        $this->markTestSkipped('crm_delivery_products table not present in this test schema');
    }
});

/**
 * The caller-supplied view data every delivery blade reads, minus the entities
 * themselves. Mirrors what TemplatePreviewController::sampleData() assembles.
 */
function deliveryViewData(Delivery $delivery, Order $order): array
{
    return [
        'delivery' => $delivery,
        'order' => $order,
        'dateFormat' => 'M j, Y',
        'taxName' => 'Tax',
        'contactDetails' => null,
        'paymentInstructions' => null,
        'fromName' => 'Sample Organization',
        'logo' => null,
        'email' => null,
        'phone' => null,
        'address' => PdfSampleData::address(),
        'organization_address' => PdfSampleData::address(),
    ];
}

dataset('delivery template slugs', ['modern', 'classic', 'bold', 'compact', 'professional']);

test('a delivery line with no order product renders instead of throwing', function (string $slug) {
    $order = Order::create([]);
    $delivery = Delivery::create(['order_id' => $order->id]);
    // Nullable by schema, and the blades' quantity filter does not exclude it.
    $delivery->deliveryProducts()->create([
        'order_product_id' => null,
        'quantity' => 2,
    ]);

    $html = View::make(
        PdfTemplateRegistry::viewFor('delivery', $slug),
        deliveryViewData($delivery->fresh(), $order)
    )->render();

    // The row is still printed — just with empty item and comment cells.
    expect($html)->toContain('2');
})->with('delivery template slugs');

test('a delivery line whose product was deleted still names the product', function (string $slug) {
    $product = Product::create(['name' => 'Retired Widget']);
    $order = Order::create([]);
    $orderProduct = $order->orderProducts()->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'comments' => 'Handle with care',
    ]);
    $delivery = Delivery::create(['order_id' => $order->id]);
    $delivery->deliveryProducts()->create([
        'order_product_id' => $orderProduct->id,
        'quantity' => 1,
    ]);

    $product->delete();

    $html = View::make(
        PdfTemplateRegistry::viewFor('delivery', $slug),
        deliveryViewData($delivery->fresh(), $order)
    )->render();

    expect($html)->toContain('Retired Widget')
        ->and($html)->toContain('Handle with care');
})->with('delivery template slugs');
