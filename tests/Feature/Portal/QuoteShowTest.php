<?php

use Illuminate\Support\Facades\URL;
use VentureDrake\LaravelCrm\Models\Quote;

test('portal quote show renders 200 for a valid signed link', function () {
    $quote = Quote::create([
        'title' => 'Sample quote',
        'subtotal' => 100,
        'tax' => 10,
        'total' => 110,
        'currency' => 'USD',
    ]);

    $url = URL::temporarySignedRoute(
        'laravel-crm.portal.quotes.show',
        now()->addDays(7),
        ['quote' => $quote->external_id]
    );

    $response = $this->get($url);

    $response->assertStatus(200);
    $response->assertSee('Sub total', false);
    $response->assertSee('Issued to', false);
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

test('portal quote show rejects an unsigned link', function () {
    $quote = Quote::create([
        'title' => 'Unsigned',
        'currency' => 'USD',
    ]);

    $response = $this->get('/p/quotes/'.$quote->external_id);

    $response->assertStatus(401);
});

test('portal quote show embeds the record\'s template, defaulting to modern', function () {
    $quote = Quote::create([
        'title' => 'Sample quote',
        'subtotal' => 100,
        'tax' => 10,
        'total' => 110,
        'currency' => 'USD',
    ]);

    $url = URL::temporarySignedRoute(
        'laravel-crm.portal.quotes.show',
        now()->addDays(7),
        ['quote' => $quote->external_id]
    );

    $response = $this->get($url);

    $response->assertStatus(200);
    // The template's scoped wrapper class — proof the page is rendering the
    // actual PDF blade rather than a second hand-rolled layout.
    $response->assertSee('modern-pdf', false);
    $response->assertDontSee('bold-pdf', false);
});

test('portal quote show follows the template picked on the record', function () {
    $quote = Quote::create([
        'title' => 'Sample quote',
        'subtotal' => 100,
        'tax' => 10,
        'total' => 110,
        'currency' => 'USD',
        'pdf_template' => 'bold',
    ]);

    $url = URL::temporarySignedRoute(
        'laravel-crm.portal.quotes.show',
        now()->addDays(7),
        ['quote' => $quote->external_id]
    );

    $response = $this->get($url);

    $response->assertStatus(200);
    $response->assertSee('bold-pdf', false);
    $response->assertDontSee('modern-pdf', false);
});

test('the quote document frame denies scripts while staying same-origin', function () {
    $quote = Quote::create([
        'title' => 'Sample quote',
        'currency' => 'USD',
    ]);

    $url = URL::temporarySignedRoute(
        'laravel-crm.portal.quotes.show',
        now()->addDays(7),
        ['quote' => $quote->external_id]
    );

    $response = $this->get($url);

    $response->assertStatus(200);
    $response->assertSee('sandbox="allow-same-origin"', false);
    $response->assertDontSee('allow-scripts', false);
});
