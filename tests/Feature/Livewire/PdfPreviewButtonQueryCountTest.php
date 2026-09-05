<?php

use Illuminate\Support\Facades\DB;
use VentureDrake\LaravelCrm\Livewire\Deliveries\DeliveryIndex;
use VentureDrake\LaravelCrm\Livewire\Deliveries\DeliveryRelatedIndex;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceIndex;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceRelatedIndex;
use VentureDrake\LaravelCrm\Livewire\Orders\OrderIndex;
use VentureDrake\LaravelCrm\Livewire\Orders\OrderRelatedIndex;
use VentureDrake\LaravelCrm\Livewire\PurchaseOrders\PurchaseOrderIndex;
use VentureDrake\LaravelCrm\Livewire\PurchaseOrders\PurchaseOrderRelatedIndex;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;

/*
 * The PDF preview button takes a `:title`, and on four of the five document
 * types that title is an accessor over unloaded belongsTo relations:
 *
 *   Invoice/Order/PurchaseOrder::getTitleAttribute()
 *       -> $this->organization->name ?? $this->person->name
 *   Delivery::getTitleAttribute()
 *       -> $this->order->client->name ?? $this->order->organization->name
 *
 * Dropping that into an index row turned every table into an N+1 — one to
 * three extra queries per row, so ~75 on a 25-row deliveries page. These
 * tests assert the property directly rather than by counting the whole
 * render: after the list query has run, reading `title` on every row must
 * cost nothing. That holds no matter what else on the page queries, and it
 * fails loudly if someone drops the eager loads.
 *
 * The paginated index components and the `#[Computed]` related-index
 * components are separate query paths and are covered separately; the four
 * doc types that carry a preview button appear in both.
 */

beforeEach(function () {
    $this->actingAsUser(['crm_access' => 1]);
});

/**
 * Read `title` on every row of `$rows` and return the queries that took.
 *
 * @return array<int, string>
 */
function queriesReadingTitles(iterable $rows): array
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    foreach ($rows as $row) {
        $row->title;
    }

    $queries = array_map(fn ($query) => $query['query'], DB::getQueryLog());

    DB::disableQueryLog();

    return $queries;
}

/**
 * Money columns every document blade formats, so a fixture is never the
 * reason a row fails to render.
 *
 * @return array<string, mixed>
 */
function previewRowMoney(): array
{
    return ['subtotal' => 100, 'tax' => 10, 'total' => 110, 'currency' => 'USD'];
}

/**
 * Seed `$count` documents of `$docType` against one organization, and return
 * the parent Order they all hang off (the related-index tests need it).
 */
function seedPreviewRows(string $docType, int $count = 5): Order
{
    $organization = Organization::create(['name' => 'Preview Org']);
    $parent = Order::create(previewRowMoney() + ['organization_id' => $organization->id]);

    for ($i = 0; $i < $count; $i++) {
        match ($docType) {
            'invoice' => Invoice::create(previewRowMoney() + [
                'organization_id' => $organization->id,
                'order_id' => $parent->id,
            ]),
            'order' => Order::create(previewRowMoney() + ['organization_id' => $organization->id]),
            'purchase-order' => PurchaseOrder::create(previewRowMoney() + [
                'organization_id' => $organization->id,
                'order_id' => $parent->id,
            ]),
            'delivery' => Delivery::create(['order_id' => $parent->id]),
        };
    }

    return $parent;
}

dataset('previewIndexComponents', [
    'invoice' => ['invoice', InvoiceIndex::class, 'invoices'],
    'order' => ['order', OrderIndex::class, 'orders'],
    'purchase-order' => ['purchase-order', PurchaseOrderIndex::class, 'purchaseOrders'],
    'delivery' => ['delivery', DeliveryIndex::class, 'deliveries'],
]);

it('reads the preview button title off an index page without extra queries', function (
    string $docType,
    string $component,
    string $method
) {
    seedPreviewRows($docType);

    $rows = app($component)->{$method}();

    expect($rows)->not->toBeEmpty();
    expect(queriesReadingTitles($rows))->toBe([]);
})->with('previewIndexComponents');

dataset('previewRelatedIndexComponents', [
    'invoice' => ['invoice', InvoiceRelatedIndex::class, 'invoices'],
    'purchase-order' => ['purchase-order', PurchaseOrderRelatedIndex::class, 'purchaseOrders'],
    'delivery' => ['delivery', DeliveryRelatedIndex::class, 'deliveries'],
]);

it('reads the preview button title off a related index without extra queries', function (
    string $docType,
    string $component,
    string $method
) {
    // Invoices, purchase orders and deliveries all hang off an Order, which
    // is the parent these tables are actually rendered under (order-show).
    $parent = seedPreviewRows($docType);

    $related = app($component);
    $related->model = $parent;

    $rows = $related->{$method}();

    expect($rows)->not->toBeEmpty();
    expect(queriesReadingTitles($rows))->toBe([]);
})->with('previewRelatedIndexComponents');

it('reads the preview button title off a quote-related order index without extra queries', function () {
    // OrderRelatedIndex is the odd one out: orders() lives on Quote, not Order.
    $organization = Organization::create(['name' => 'Preview Org']);
    $quote = Quote::create(previewRowMoney() + ['title' => 'Parent quote']);

    for ($i = 0; $i < 5; $i++) {
        Order::create(previewRowMoney() + [
            'organization_id' => $organization->id,
            'quote_id' => $quote->id,
        ]);
    }

    $related = app(OrderRelatedIndex::class);
    $related->model = $quote;

    $rows = $related->orders();

    expect($rows)->not->toBeEmpty();
    expect(queriesReadingTitles($rows))->toBe([]);
});
