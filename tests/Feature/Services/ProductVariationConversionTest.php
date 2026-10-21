<?php

/*
 * A line's product variation follows it through every conversion: deal to
 * quote, quote to order, order to invoice, purchase order and delivery.
 *
 * Each step is driven the way the forms drive it - the rows crm-model-products
 * mounts from the source document are what the service receives - so these
 * cover the row keys as well as the services.
 */

use Illuminate\Http\Request;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\ModelProducts;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductPrice;
use VentureDrake\LaravelCrm\Models\ProductVariation;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\PurchaseOrderLine;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Services\InvoiceService;
use VentureDrake\LaravelCrm\Services\OrderService;
use VentureDrake\LaravelCrm\Services\PurchaseOrderService;
use VentureDrake\LaravelCrm\Services\QuoteService;

beforeEach(function () {
    $this->user = $this->actingAsUser();

    $this->widget = Product::create(['name' => 'Widget']);
    ProductPrice::create(['product_id' => $this->widget->id, 'unit_price' => 10, 'currency' => 'USD']);

    $this->large = ProductVariation::create(['product_id' => $this->widget->id, 'name' => 'Large', 'order' => 2]);
    ProductPrice::create([
        'product_id' => $this->widget->id, 'product_variation_id' => $this->large->id,
        'unit_price' => 15, 'currency' => 'USD',
    ]);

    $this->small = ProductVariation::create(['product_id' => $this->widget->id, 'name' => 'Small', 'order' => 1]);
    ProductPrice::create([
        'product_id' => $this->widget->id, 'product_variation_id' => $this->small->id,
        'unit_price' => 12, 'currency' => 'USD',
    ]);

    $this->plain = Product::create(['name' => 'Plain']);
    ProductPrice::create(['product_id' => $this->plain->id, 'unit_price' => 4, 'currency' => 'USD']);
});

function conversionDeal(Product $product, ProductVariation $variation): Deal
{
    $deal = Deal::create(['title' => 'Deal', 'currency' => 'USD']);

    $deal->dealProducts()->create([
        'product_id' => $product->id,
        'product_variation_id' => $variation->id,
        'quantity' => 2,
        'price' => 15,
        'amount' => 30,
        'currency' => 'USD',
    ]);

    return $deal;
}

/**
 * The rows crm-model-products would hand the parent form.
 */
function conversionRows(array $params): array
{
    return Livewire::test(ModelProducts::class, $params + ['currency' => 'USD'])->get('products');
}

function quoteFromDeal(Deal $deal, $user): Quote
{
    return app(QuoteService::class)->create(new Request([
        'title' => 'Quote', 'currency' => 'USD', 'user_owner_id' => $user->id,
        'products' => conversionRows(['model' => $deal]),
    ]));
}

function orderFromQuote(Quote $quote, $user): Order
{
    return app(OrderService::class)->create(new Request([
        'quote_id' => $quote->id, 'currency' => 'USD', 'user_owner_id' => $user->id,
        'products' => conversionRows(['model' => $quote, 'from' => 'Quote', 'creating' => 'Order']),
    ]));
}

function invoiceFromOrder(Order $order): Invoice
{
    return app(InvoiceService::class)->create(new Request([
        'order_id' => $order->id, 'currency' => 'USD',
        'issue_date' => '2026-01-01', 'due_date' => '2026-01-31',
        'products' => conversionRows(['model' => $order, 'from' => 'Order', 'creating' => 'Invoice']),
    ]));
}

function purchaseOrderFromOrder(Order $order): PurchaseOrder
{
    return app(PurchaseOrderService::class)->create(new Request([
        'order_id' => $order->id, 'currency' => 'USD', 'delivery_type' => 'pickup',
        'products' => conversionRows(['model' => $order, 'from' => 'Order', 'creating' => 'PurchaseOrder']),
    ]));
}

test('a variation survives deal to quote to order to invoice, purchase order and delivery', function () {
    $deal = conversionDeal($this->widget, $this->large);

    $quote = quoteFromDeal($deal, $this->user);
    $quoteProduct = $quote->quoteProducts()->sole();

    expect($quoteProduct->product_variation_id)->toBe($this->large->id)
        ->and($quoteProduct->displayName())->toBe('Widget — Large')
        ->and((int) $quoteProduct->getRawOriginal('price'))->toBe(1500);

    $order = orderFromQuote($quote, $this->user);
    $orderProduct = $order->orderProducts()->sole();

    expect($orderProduct->product_variation_id)->toBe($this->large->id)
        ->and($orderProduct->quote_product_id)->toBe($quoteProduct->id);

    $invoiceLine = invoiceFromOrder($order)->invoiceLines()->sole();

    expect($invoiceLine->product_variation_id)->toBe($this->large->id)
        ->and($invoiceLine->order_product_id)->toBe($orderProduct->id);

    $purchaseOrderLine = purchaseOrderFromOrder($order)->purchaseOrderLines()->sole();

    expect($purchaseOrderLine->product_variation_id)->toBe($this->large->id)
        ->and($purchaseOrderLine->order_product_id)->toBe($orderProduct->id)
        ->and($purchaseOrderLine->displayName())->toBe('Widget — Large');

    $deliveryRows = conversionRows(['model' => $order, 'from' => 'Order', 'creating' => 'Delivery']);

    expect($deliveryRows)->toHaveCount(1)
        ->and($deliveryRows[0]['order_product_id'])->toBe($orderProduct->id)
        ->and($deliveryRows[0]['product_variation_id'])->toBe($this->large->id)
        ->and($deliveryRows[0]['name'])->toBe('Widget — Large');
});

test('a converted line keeps a variation deleted after the source line was created', function () {
    $deal = conversionDeal($this->widget, $this->large);

    $this->large->delete();

    $quote = quoteFromDeal($deal, $this->user);
    expect($quote->quoteProducts()->sole()->product_variation_id)->toBe($this->large->id);

    $order = orderFromQuote($quote, $this->user);
    expect($order->orderProducts()->sole()->product_variation_id)->toBe($this->large->id);

    expect(invoiceFromOrder($order)->invoiceLines()->sole()->product_variation_id)->toBe($this->large->id)
        ->and(purchaseOrderFromOrder($order)->purchaseOrderLines()->sole()->product_variation_id)->toBe($this->large->id);

    // The read-only rows still name it.
    $rows = conversionRows(['model' => $order, 'from' => 'Order', 'creating' => 'Invoice']);
    expect($rows[0]['name'])->toBe('Widget — Large');
});

test('a product line with no variation converts exactly as before', function () {
    $deal = Deal::create(['title' => 'Deal', 'currency' => 'USD']);
    $deal->dealProducts()->create([
        'product_id' => $this->plain->id, 'quantity' => 1, 'price' => 4, 'amount' => 4, 'currency' => 'USD',
    ]);

    $quote = quoteFromDeal($deal, $this->user);
    $quoteProduct = $quote->quoteProducts()->sole();

    expect($quoteProduct->product_variation_id)->toBeNull()
        ->and($quoteProduct->displayName())->toBe('Plain');

    $order = orderFromQuote($quote, $this->user);
    expect($order->orderProducts()->sole()->product_variation_id)->toBeNull();
});

test('editing a purchase order updates its lines and removes the dropped ones', function () {
    $purchaseOrder = app(PurchaseOrderService::class)->create(new Request([
        'currency' => 'USD', 'delivery_type' => 'pickup',
        'products' => [
            [
                'id' => $this->widget->id, 'product_variation_id' => $this->large->id,
                'quantity' => 1, 'unit_price' => 15, 'amount' => 15, 'comments' => null,
            ],
            [
                'id' => $this->plain->id, 'product_variation_id' => null,
                'quantity' => 2, 'unit_price' => 4, 'amount' => 8, 'comments' => null,
            ],
        ],
    ]));

    [$widgetLine, $plainLine] = $purchaseOrder->purchaseOrderLines()->orderBy('id')->get()->all();

    expect($widgetLine->product_variation_id)->toBe($this->large->id);

    // Edit the way the form does: change the first line's variation and
    // quantity, remove the second.
    $products = Livewire::test(ModelProducts::class, ['model' => $purchaseOrder, 'creating' => 'PurchaseOrder', 'currency' => 'USD'])
        ->set('products.0.product_variation_id', $this->small->id)
        ->set('products.0.quantity', 3)
        ->call('remove', 1)
        ->get('products');

    expect($products)->toHaveCount(1)
        ->and($products[0]['purchase_order_line_id'])->toBe($widgetLine->id)
        ->and($products[0]['unit_price'])->toEqual(12);

    app(PurchaseOrderService::class)->update(new Request([
        'currency' => 'USD', 'delivery_type' => 'pickup',
        'sub_total' => 36, 'tax' => 0, 'total' => 36,
        'products' => $products,
    ]), $purchaseOrder);

    $widgetLine->refresh();

    expect($widgetLine->product_variation_id)->toBe($this->small->id)
        ->and((float) $widgetLine->quantity)->toEqual(3.0)
        ->and((int) $widgetLine->getRawOriginal('price'))->toBe(1200)
        ->and((int) $widgetLine->getRawOriginal('amount'))->toBe(3600)
        ->and($purchaseOrder->purchaseOrderLines()->count())->toBe(1)
        ->and(PurchaseOrderLine::withTrashed()->find($plainLine->id)->trashed())->toBeTrue();
});

test('a quote from a deal keeps a line whose product has since been deleted', function () {
    $deal = conversionDeal($this->widget, $this->large);

    $this->widget->delete();

    $quoteProduct = quoteFromDeal($deal, $this->user)->quoteProducts()->sole();

    expect($quoteProduct->product_id)->toBe($this->widget->id)
        ->and($quoteProduct->product_variation_id)->toBe($this->large->id)
        ->and(Product::withTrashed()->count())->toBe(2);
});

test('editing a purchase order keeps a line whose product has since been deleted', function () {
    $purchaseOrder = app(PurchaseOrderService::class)->create(new Request([
        'currency' => 'USD', 'delivery_type' => 'pickup',
        'products' => [[
            'id' => $this->widget->id, 'product_variation_id' => $this->large->id,
            'quantity' => 1, 'unit_price' => 15, 'amount' => 15, 'comments' => null,
        ]],
    ]));

    $line = $purchaseOrder->purchaseOrderLines()->sole();

    $this->widget->delete();

    $products = conversionRows(['model' => $purchaseOrder, 'creating' => 'PurchaseOrder']);

    app(PurchaseOrderService::class)->update(new Request([
        'currency' => 'USD', 'delivery_type' => 'pickup', 'reference' => 'Changed',
        'sub_total' => 15, 'tax' => 0, 'total' => 15,
        'products' => $products,
    ]), $purchaseOrder);

    $line->refresh();

    expect($line->product_id)->toBe($this->widget->id)
        ->and($line->product_variation_id)->toBe($this->large->id)
        ->and(Product::withTrashed()->count())->toBe(2);
});
