<?php

use Illuminate\Support\Facades\View;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductVariation;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Support\PdfSampleData;
use VentureDrake\LaravelCrm\Support\PdfTemplateRegistry;

/*
 * A line sold as a variation must say which one on the printed document —
 * "Widget — Large", not just "Widget" — in every template, because each of
 * the 25 (docType × slug) blades carries its own copy of the line markup.
 * The emailed and portal copies reuse these blades, so this covers them too.
 *
 * Rendered through View::make rather than DomPDF, as in
 * DeliveryPdfLineItemTest: the assertion wants readable HTML.
 */

/**
 * The caller-supplied view data every PDF blade reads, minus the entities.
 * Mirrors what TemplatePreviewController::sampleData() assembles.
 */
function variationPdfViewData(array $entities): array
{
    return array_merge([
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
    ], $entities);
}

/**
 * One saved document of `$docType` with one line for `$product`, sold as
 * `$variation` when given. Returns the view data its PDF blade needs.
 */
function variationPdfDocument(string $docType, Product $product, ?ProductVariation $variation): array
{
    $line = [
        'product_id' => $product->id,
        'product_variation_id' => $variation?->id,
        'quantity' => 2,
        'price' => 1000,
        'amount' => 2000,
        'currency' => 'USD',
    ];

    switch ($docType) {
        case 'quote':
            $quote = Quote::create(['title' => 'Widget quote', 'currency' => 'USD']);
            $quote->quoteProducts()->create($line);

            return ['quote' => $quote->fresh()];

        case 'order':
            $order = Order::create(['currency' => 'USD']);
            $order->orderProducts()->create($line);

            return ['order' => $order->fresh()];

        case 'invoice':
            $invoice = Invoice::create(['currency' => 'USD']);
            $invoice->invoiceLines()->create($line);

            return ['invoice' => $invoice->fresh()];

        case 'purchase-order':
            $purchaseOrder = PurchaseOrder::create(['currency' => 'USD']);
            $purchaseOrder->purchaseOrderLines()->create($line);

            return ['purchaseOrder' => $purchaseOrder->fresh()];

        case 'delivery':
            // Delivery lines carry no variation of their own — they read it
            // through the order product they were raised from.
            $order = Order::create(['currency' => 'USD']);
            $orderProduct = $order->orderProducts()->create($line);
            $delivery = Delivery::create(['order_id' => $order->id]);
            $delivery->deliveryProducts()->create([
                'order_product_id' => $orderProduct->id,
                'quantity' => 2,
            ]);

            return ['delivery' => $delivery->fresh(), 'order' => $order->fresh()];
    }

    throw new InvalidArgumentException("Unknown doc type [{$docType}]");
}

dataset('variation_pdf_pairs', function () {
    $rows = [];
    foreach (PdfTemplateRegistry::DOC_TYPES as $docType) {
        foreach (PdfTemplateRegistry::SLUGS as $slug) {
            $rows[$docType.'-'.$slug] = [$docType, $slug];
        }
    }

    return $rows;
});

beforeEach(function () {
    $this->product = Product::create(['name' => 'Widget', 'code' => 'WID']);
    $this->variation = ProductVariation::create([
        'product_id' => $this->product->id,
        'name' => 'Large',
        'code' => 'WID-L',
    ]);
});

test('a line sold as a variation prints the product and variation name', function (string $docType, string $slug) {
    $html = View::make(
        PdfTemplateRegistry::viewFor($docType, $slug),
        variationPdfViewData(variationPdfDocument($docType, $this->product, $this->variation))
    )->render();

    expect($html)->toContain('Widget — Large');
})->with('variation_pdf_pairs');

test('a line without a variation prints just the product name', function (string $docType, string $slug) {
    $html = View::make(
        PdfTemplateRegistry::viewFor($docType, $slug),
        variationPdfViewData(variationPdfDocument($docType, $this->product, null))
    )->render();

    expect($html)->toContain('Widget')
        ->and($html)->not->toContain('Widget —')
        ->and($html)->not->toContain('Large');
})->with('variation_pdf_pairs');

test('the line models expose the variation code over the product code', function () {
    $order = Order::create(['currency' => 'USD']);
    $withVariation = $order->orderProducts()->create([
        'product_id' => $this->product->id,
        'product_variation_id' => $this->variation->id,
        'quantity' => 1,
    ]);
    $withoutVariation = $order->orderProducts()->create([
        'product_id' => $this->product->id,
        'quantity' => 1,
    ]);

    $delivery = Delivery::create(['order_id' => $order->id]);
    $deliveryProduct = $delivery->deliveryProducts()->create([
        'order_product_id' => $withVariation->id,
        'quantity' => 1,
    ]);

    expect($withVariation->fresh()->displayCode())->toBe('WID-L')
        ->and($withoutVariation->fresh()->displayCode())->toBe('WID')
        ->and($deliveryProduct->fresh()->displayName())->toBe('Widget — Large')
        ->and($deliveryProduct->fresh()->displayCode())->toBe('WID-L');
});

test('fabricated sample lines still render their product name in every template', function (string $docType, string $slug) {
    $entities = match ($docType) {
        'quote' => ['quote' => PdfSampleData::quote()],
        'order' => ['order' => PdfSampleData::order()],
        'invoice' => ['invoice' => PdfSampleData::invoice()],
        'purchase-order' => ['purchaseOrder' => PdfSampleData::purchaseOrder()],
        'delivery' => ['delivery' => PdfSampleData::delivery(), 'order' => PdfSampleData::order()],
    };

    $lines = match ($docType) {
        'quote' => $entities['quote']->quoteProducts,
        'order' => $entities['order']->orderProducts,
        'invoice' => $entities['invoice']->invoiceLines,
        'purchase-order' => $entities['purchaseOrder']->purchaseOrderLines,
        'delivery' => $entities['delivery']->deliveryProducts,
    };

    $html = View::make(PdfTemplateRegistry::viewFor($docType, $slug), variationPdfViewData($entities))->render();

    expect($html)->toContain(e($lines->first()->displayName()))
        ->and($lines->first()->displayName())->not->toBe('');
})->with('variation_pdf_pairs');
