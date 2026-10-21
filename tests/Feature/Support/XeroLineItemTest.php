<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use VentureDrake\LaravelCrm\Models\InvoiceLine;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\XeroContact;
use VentureDrake\LaravelCrm\Models\XeroItem;
use VentureDrake\LaravelCrm\Services\InvoiceService;
use VentureDrake\LaravelCrm\Support\XeroLineItem;

beforeEach(function () {
    $this->product = Product::create(['name' => 'Widget', 'code' => 'WID']);
    $this->variation = $this->product->productVariations()->create(['name' => 'Large', 'code' => 'WID-L']);
});

function invoiceLineFor(Product $product, $variationId = null): InvoiceLine
{
    return InvoiceLine::create([
        'invoice_id' => 1,
        'product_id' => $product->id,
        'product_variation_id' => $variationId,
    ]);
}

test('a variation code is sent when Xero already has an item with that code', function () {
    XeroItem::create(['product_id' => $this->product->id, 'code' => 'WID']);
    XeroItem::create(['code' => 'WID-L']);

    expect(XeroLineItem::itemCode(invoiceLineFor($this->product, $this->variation->id)))->toBe('WID-L');
});

test('the parent item code is sent when Xero has no item for the variation code', function () {
    XeroItem::create(['product_id' => $this->product->id, 'code' => 'XERO-WID']);

    expect(XeroLineItem::itemCode(invoiceLineFor($this->product, $this->variation->id)))->toBe('XERO-WID');
});

test('the product code is sent when the product has no Xero item', function () {
    expect(XeroLineItem::itemCode(invoiceLineFor($this->product, $this->variation->id)))->toBe('WID');
    expect(XeroLineItem::itemCode(invoiceLineFor($this->product)))->toBe('WID');
});

test('a variation without a code uses the parent item', function () {
    $plain = $this->product->productVariations()->create(['name' => 'Small']);
    XeroItem::create(['code' => '']);

    expect(XeroLineItem::itemCode(invoiceLineFor($this->product, $plain->id)))->toBe('WID');
});

test('the description carries the variation name', function () {
    expect(XeroLineItem::description(invoiceLineFor($this->product, $this->variation->id)))->toBe('Widget — Large');
    expect(XeroLineItem::description(invoiceLineFor($this->product)))->toBe('Widget');
});

test('InvoiceService sends the variation item code and name to Xero', function () {
    $this->actingAsUser();

    XeroItem::create(['code' => 'WID-L']);

    $organization = Organization::create(['name' => 'Acme']);
    XeroContact::create(['organization_id' => $organization->id]);

    $xero = new class
    {
        public array $stored = [];

        public function isConnected(): bool
        {
            return true;
        }

        public function invoices()
        {
            return $this;
        }

        public function store(array $payload): array
        {
            $this->stored[] = $payload;

            return [
                'InvoiceID' => 'xero-invoice-1',
                'Type' => 'ACCREC',
                'InvoiceNumber' => 'INV-0001',
                'Reference' => $payload['Reference'] ?? null,
                'Status' => 'AUTHORISED',
            ];
        }

        public function __call($name, $args)
        {
            return null;
        }
    };

    $this->app->instance('xero', $xero);
    Facade::clearResolvedInstance('xero');

    app(InvoiceService::class)->create(new Request([
        'reference' => 'INV-1',
        'currency' => 'USD',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-31',
        'products' => [
            [
                'id' => $this->product->id,
                'product_variation_id' => $this->variation->id,
                'quantity' => 2,
                'unit_price' => '15.00',
                'amount' => '30.00',
                'comments' => null,
            ],
        ],
    ]), null, $organization);

    expect($xero->stored)->toHaveCount(1);
    expect($xero->stored[0]['LineItems'][0]['ItemCode'])->toBe('WID-L');
    expect($xero->stored[0]['LineItems'][0]['Description'])->toBe('Widget — Large');
});
