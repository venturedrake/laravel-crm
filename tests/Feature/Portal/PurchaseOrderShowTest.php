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
    $response->assertDontSee('table table-hover');
    $response->assertDontSee('card shadow-sm');
    $response->assertDontSee('navbar-expand');
    $response->assertDontSee('col-3');
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
