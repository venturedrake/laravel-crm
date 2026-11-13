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

test('portal invoice show embeds the record\'s template, defaulting to modern', function () {
    $invoice = Invoice::create([
        'invoice_id' => 'INV1004',
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
    // The template's scoped wrapper class — proof the page is rendering the
    // actual PDF blade rather than a second hand-rolled layout.
    $response->assertSee('modern-pdf', false);
    $response->assertDontSee('bold-pdf', false);
});

test('portal invoice show follows the template picked on the record', function () {
    $invoice = Invoice::create([
        'invoice_id' => 'INV1005',
        'subtotal' => 100,
        'tax' => 10,
        'total' => 110,
        'currency' => 'USD',
        'pdf_template' => 'bold',
    ]);

    $url = URL::temporarySignedRoute(
        'laravel-crm.portal.invoices.show',
        now()->addDays(7),
        ['invoice' => $invoice->external_id]
    );

    $response = $this->get($url);

    $response->assertStatus(200);
    $response->assertSee('bold-pdf', false);
    $response->assertDontSee('modern-pdf', false);
});

test('the document frame denies scripts while staying same-origin', function () {
    $invoice = Invoice::create([
        'invoice_id' => 'INV1006',
        'currency' => 'USD',
    ]);

    $url = URL::temporarySignedRoute(
        'laravel-crm.portal.invoices.show',
        now()->addDays(7),
        ['invoice' => $invoice->external_id]
    );

    $response = $this->get($url);

    // The templates emit record content unescaped, which is harmless in
    // DomPDF but would be stored XSS in a browser on a public page. Dropping
    // allow-scripts is what neuters it; allow-same-origin is what lets the
    // parent measure the frame to size it.
    $response->assertStatus(200);
    $response->assertSee('sandbox="allow-same-origin"', false);
    $response->assertDontSee('allow-scripts', false);
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

test('portal invoice show lists paid and balance due after a part-payment', function () {
    $invoice = Invoice::create([
        'invoice_id' => 'INV1007',
        'subtotal' => 1045.35,
        'tax' => 104.54,
        'total' => 1149.89,
        'amount_paid' => 100,
        'currency' => 'USD',
    ]);

    $url = URL::temporarySignedRoute(
        'laravel-crm.portal.invoices.show',
        now()->addDays(7),
        ['invoice' => $invoice->external_id]
    );

    $response = $this->get($url);

    $response->assertStatus(200);
    $response->assertSee('Balance due', false);
    $response->assertSee('$100.00', false);
    $response->assertSee('$1,049.89', false);
    // A part-payment leaves the invoice unpaid. Check the badge class rather
    // than the absence of `badge-success`: the inlined document.css contains
    // that class name on every page.
    $response->assertSee('Unpaid', false);
    $response->assertSee('badge-neutral', false);
});

test('portal invoice show shows a zero balance due once fully paid', function () {
    $invoice = Invoice::create([
        'invoice_id' => 'INV1008',
        'subtotal' => 100,
        'tax' => 10,
        'total' => 110,
        'amount_paid' => 110,
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
    $response->assertSee('Balance due', false);
    $response->assertSee('$0.00', false);
    $response->assertSee('badge-success', false);
});

test('portal invoice show hides the balance row when nothing has been paid', function () {
    $invoice = Invoice::create([
        'invoice_id' => 'INV1009',
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
    $response->assertDontSee('Balance due', false);
});

test('portal invoice show rejects an unsigned link', function () {
    $invoice = Invoice::create([
        'invoice_id' => 'INV1003',
        'currency' => 'USD',
    ]);

    $response = $this->get('/p/invoices/'.$invoice->external_id);

    $response->assertStatus(401);
});
