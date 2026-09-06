<?php

use Illuminate\Support\Facades\URL;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;

test('portal purchase order show renders 200 for a valid signed link', function () {
    $purchaseOrder = PurchaseOrder::create([
        'subtotal' => 100,
        'tax' => 10,
        'total' => 110,
        'currency' => 'USD',
    ]);

    $url = URL::temporarySignedRoute(
        'laravel-crm.portal.purchase-orders.show',
        now()->addDays(7),
        ['purchaseOrder' => $purchaseOrder->external_id]
    );

    $response = $this->get($url);

    $response->assertStatus(200);
    $response->assertSee('Sub total', false);
    $response->assertSee('Total', false);
    $response->assertSee('Download', false);
    // The `navbar-expand` / `navbar-brand` / `col-3` guards that used to sit
    // here are gone deliberately. They asserted "no legacy v1 Bootstrap
    // markup on this page", but the embedded document inlines the shipped
    // Bootstrap-based document.css into its srcdoc, so those class names now
    // appear as stylesheet text for every template and the assertion no
    // longer distinguishes anything. The markup guards below still do, and
    // the template-embedding tests are what actually pin the page's content.
    $response->assertDontSee('table table-hover');
    $response->assertDontSee('card shadow-sm');
});

test('portal purchase order show preserves signed URL on the download form action', function () {
    $purchaseOrder = PurchaseOrder::create([
        'subtotal' => 100,
        'tax' => 10,
        'total' => 110,
        'currency' => 'USD',
    ]);

    $url = URL::temporarySignedRoute(
        'laravel-crm.portal.purchase-orders.show',
        now()->addDays(7),
        ['purchaseOrder' => $purchaseOrder->external_id]
    );

    $response = $this->get($url);

    $response->assertStatus(200);
    $response->assertSee('signature=', false);
    $response->assertSee('expires=', false);
    $response->assertSee('name="action" value="download"', false);
});

test('portal purchase order show rejects an unsigned link', function () {
    $purchaseOrder = PurchaseOrder::create([
        'currency' => 'USD',
    ]);

    $response = $this->get('/p/purchase-orders/'.$purchaseOrder->external_id);

    $response->assertStatus(401);
});

test('portal purchase order show embeds the record\'s template, defaulting to modern', function () {
    $purchaseOrder = PurchaseOrder::create([
        'subtotal' => 100,
        'tax' => 10,
        'total' => 110,
        'currency' => 'USD',
    ]);

    $url = URL::temporarySignedRoute(
        'laravel-crm.portal.purchase-orders.show',
        now()->addDays(7),
        ['purchaseOrder' => $purchaseOrder->external_id]
    );

    $response = $this->get($url);

    $response->assertStatus(200);
    // The template's scoped wrapper class — proof the page is rendering the
    // actual PDF blade rather than a second hand-rolled layout.
    $response->assertSee('modern-pdf', false);
    $response->assertDontSee('bold-pdf', false);
});

test('portal purchase order show follows the template picked on the record', function () {
    $purchaseOrder = PurchaseOrder::create([
        'subtotal' => 100,
        'tax' => 10,
        'total' => 110,
        'currency' => 'USD',
        'pdf_template' => 'bold',
    ]);

    $url = URL::temporarySignedRoute(
        'laravel-crm.portal.purchase-orders.show',
        now()->addDays(7),
        ['purchaseOrder' => $purchaseOrder->external_id]
    );

    $response = $this->get($url);

    $response->assertStatus(200);
    $response->assertSee('bold-pdf', false);
    $response->assertDontSee('modern-pdf', false);
});

test('the purchase order document frame denies scripts while staying same-origin', function () {
    $purchaseOrder = PurchaseOrder::create([
        'currency' => 'USD',
    ]);

    $url = URL::temporarySignedRoute(
        'laravel-crm.portal.purchase-orders.show',
        now()->addDays(7),
        ['purchaseOrder' => $purchaseOrder->external_id]
    );

    $response = $this->get($url);

    $response->assertStatus(200);
    $response->assertSee('sandbox="allow-same-origin"', false);
    $response->assertDontSee('allow-scripts', false);
});
