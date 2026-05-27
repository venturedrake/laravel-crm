<?php

use Illuminate\Support\Facades\URL;
use VentureDrake\LaravelCrm\Models\Invoice;

test('portal invoice show renders 200 for a valid signed link', function () {
    $invoice = Invoice::create([
        'invoice_id' => 'INV1001',
        'subtotal' => 100,
        'tax' => 10,
        'total' => 110,
        'currency' => 'USD',
    ]);

    $url = URL::temporarySignedRoute(
        'laravel-crm.portal.invoices.show',
        now()->addDays(7),
        ['invoice' => $invoice->external_id]
    );

    $response = $this->get($url);

    $response->assertStatus(200);
    $response->assertSee('Sub total', false);
    $response->assertSee('Total', false);
    $response->assertDontSee('table table-hover');
    $response->assertDontSee('card shadow-sm');
    $response->assertDontSee('navbar-expand');
    $response->assertDontSee('col-3');
});

test('portal invoice show renders paid badge when fully_paid_at is set', function () {
    $invoice = Invoice::create([
        'invoice_id' => 'INV1002',
        'subtotal' => 100,
        'tax' => 10,
        'total' => 110,
        'currency' => 'USD',
        'fully_paid_at' => now(),
    ]);

    $url = URL::temporarySignedRoute(
        'laravel-crm.portal.invoices.show',
        now()->addDays(7),
        ['invoice' => $invoice->external_id]
    );

    $response = $this->get($url);

    $response->assertStatus(200);
    $response->assertSee('Paid', false);
    $response->assertSee('badge-success', false);
});

test('portal invoice show rejects an unsigned link', function () {
    $invoice = Invoice::create([
        'invoice_id' => 'INV1003',
        'currency' => 'USD',
    ]);

    $response = $this->get('/p/invoices/'.$invoice->external_id);

    $response->assertStatus(401);
});
