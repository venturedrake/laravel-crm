<?php

use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

/**
 * `subtotal` and `total` were accepted on quote / order / invoice writes at
 * 2.3.0 and are now computed from `line_items`. They are `prohibited` rather
 * than merely dropped from the rules, so a client still posting authoritative
 * totals gets a 422 naming the cause instead of silently different numbers
 * back. `prohibited` passes for an absent or empty value, so a payload that
 * never sent them is unaffected.
 */
function totalsApiUser(): User
{
    return User::create([
        'name' => 'Totals API User',
        'email' => 'totals-api-'.uniqid().'@example.com',
        'password' => bcrypt('secret-password'),
        'crm_access' => true,
    ]);
}

function totalsApiHeaders(User $user): array
{
    return [
        'Authorization' => 'Bearer '.$user->createToken('totals-api-test')->plainTextToken,
        'Accept' => 'application/json',
    ];
}

function totalsApiLineItems(): array
{
    // Taxed at 10%: the header tax is summed from the lines, not posted.
    $gst = TaxRate::create(['name' => 'GST', 'rate' => 10, 'default' => false]);
    $product = Product::create(['name' => 'Widget', 'tax_rate_id' => $gst->id])->refresh();

    return [[
        'product_id' => $product->external_id,
        'quantity' => 2,
        'unit_price' => 50.00,
        'amount' => 100.00,
    ]];
}

dataset('totals resources', [
    'quotes' => ['quotes', ['title' => 'Proposal'], Quote::class],
    'orders' => ['orders', ['reference' => 'O-REF-1'], Order::class],
    'invoices' => ['invoices', ['reference' => 'INV-REF-1'], Invoice::class],
]);

test('POST rejects subtotal and total with a 422 naming line_items', function (string $resource, array $attributes) {
    $response = $this->withHeaders(totalsApiHeaders(totalsApiUser()))
        ->postJson('/crm/api/v2/'.$resource, $attributes + [
            'currency' => 'USD',
            'subtotal' => 999.00,
            'total' => 999.00,
            'line_items' => totalsApiLineItems(),
        ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['subtotal', 'total']);

    expect($response->json('errors.subtotal.0'))->toContain('line_items')
        ->and($response->json('errors.total.0'))->toContain('line_items');
})->with('totals resources');

test('POST still accepts a payload that omits them, and computes both', function (string $resource, array $attributes, string $modelClass) {
    $response = $this->withHeaders(totalsApiHeaders(totalsApiUser()))
        ->postJson('/crm/api/v2/'.$resource, $attributes + [
            'currency' => 'USD',
            // Ignored: the header tax is the lines' tax (10% of 100).
            'tax' => 99.00,
            'line_items' => totalsApiLineItems(),
        ]);

    $response->assertStatus(201);

    expect($response->json('data.subtotal'))->toEqual(100.00)
        ->and($response->json('data.tax'))->toEqual(10.00)
        ->and($response->json('data.total'))->toEqual(110.00)
        ->and($modelClass::where('external_id', $response->json('data.id'))->exists())->toBeTrue();
})->with('totals resources');

test('PUT rejects subtotal and total with a 422', function (string $resource, array $attributes) {
    $headers = totalsApiHeaders(totalsApiUser());

    $created = $this->withHeaders($headers)
        ->postJson('/crm/api/v2/'.$resource, $attributes + [
            'currency' => 'USD',
            'line_items' => totalsApiLineItems(),
        ])->assertStatus(201);

    $this->withHeaders($headers)
        ->putJson('/crm/api/v2/'.$resource.'/'.$created->json('data.id'), $attributes + [
            'subtotal' => 999.00,
            'total' => 999.00,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['subtotal', 'total']);
})->with('totals resources');

test('a null subtotal or total is treated as absent, not as a violation', function (string $resource, array $attributes) {
    $this->withHeaders(totalsApiHeaders(totalsApiUser()))
        ->postJson('/crm/api/v2/'.$resource, $attributes + [
            'currency' => 'USD',
            'subtotal' => null,
            'total' => null,
            'line_items' => totalsApiLineItems(),
        ])
        ->assertStatus(201);
})->with('totals resources');
