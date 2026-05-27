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
    $response->assertDontSee('table table-hover');
    $response->assertDontSee('card shadow-sm');
    $response->assertDontSee('navbar-expand');
    $response->assertDontSee('col-3');
});

test('portal quote show rejects an unsigned link', function () {
    $quote = Quote::create([
        'title' => 'Unsigned',
        'currency' => 'USD',
    ]);

    $response = $this->get('/p/quotes/'.$quote->external_id);

    $response->assertStatus(401);
});
