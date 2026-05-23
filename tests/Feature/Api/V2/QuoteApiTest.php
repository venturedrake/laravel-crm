<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\QuoteProduct;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

function quoteApiUser(array $attributes = []): User
{
    return User::create(array_merge([
        'name' => 'Quote API User',
        'email' => 'quote-api-'.uniqid().'@example.com',
        'password' => bcrypt('secret-password'),
        'crm_access' => true,
    ], $attributes));
}

function quoteApiHeaders(User $user): array
{
    $token = $user->createToken('quote-api-test')->plainTextToken;

    return [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
    ];
}

function createApiProduct(string $name = 'Widget'): Product
{
    $product = Product::create([
        'name' => $name,
    ]);

    return $product->refresh();
}

test('GET /quotes returns 401 when unauthenticated', function () {
    $this->getJson('/crm/api/v2/quotes')->assertStatus(401);
});

test('POST /quotes returns 401 when unauthenticated', function () {
    $this->postJson('/crm/api/v2/quotes', ['title' => 'Hello'])->assertStatus(401);
});

test('POST /quotes creates a quote with nested line items', function () {
    $user = quoteApiUser();
    $organization = Organization::create(['name' => 'Acme Co']);
    $productA = createApiProduct('Widget A');
    $productB = createApiProduct('Widget B');

    $response = $this->withHeaders(quoteApiHeaders($user))
        ->postJson('/crm/api/v2/quotes', [
            'title' => 'Initial proposal',
            'reference' => 'Q-REF-1',
            'currency' => 'USD',
            'subtotal' => 250.00,
            'tax' => 25.00,
            'total' => 275.00,
            'organization_id' => $organization->external_id,
            'user_owner_id' => $user->id,
            'line_items' => [
                [
                    'product_id' => $productA->external_id,
                    'quantity' => 2,
                    'unit_price' => 50.00,
                    'amount' => 100.00,
                    'comments' => 'Bulk',
                ],
                [
                    'product_id' => $productB->external_id,
                    'quantity' => 3,
                    'unit_price' => 50.00,
                    'amount' => 150.00,
                    'comments' => null,
                ],
            ],
        ]);

    $response->assertStatus(201);

    $payload = $response->json('data');
    expect(Str::isUuid($payload['id']))->toBeTrue();
    expect($payload['title'])->toBe('Initial proposal');
    expect($payload['subtotal'])->toEqual(250.00);
    expect($payload['tax'])->toEqual(25.00);
    expect($payload['total'])->toEqual(275.00);
    expect($payload['organization']['id'])->toBe($organization->external_id);
    expect($payload['line_items'])->toHaveCount(2);
    expect($payload['line_items'][0]['product_id'])->toBe($productA->external_id);
    expect($payload['line_items'][0]['quantity'])->toBe(2);
    expect($payload['line_items'][0]['unit_price'])->toEqual(50.00);
    expect($payload['line_items'][0]['amount'])->toEqual(100.00);

    // Verify cents storage on the underlying rows.
    $stored = Quote::where('external_id', $payload['id'])->first();
    expect((int) $stored->subtotal)->toBe(25000);
    expect((int) $stored->total)->toBe(27500);
    expect($stored->quoteProducts)->toHaveCount(2);
    expect((int) $stored->quoteProducts->first()->price)->toBe(5000);
    expect((int) $stored->quoteProducts->first()->amount)->toBe(10000);
});

test('POST /quotes returns 422 when required fields are missing', function () {
    $user = quoteApiUser();

    $this->withHeaders(quoteApiHeaders($user))
        ->postJson('/crm/api/v2/quotes', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['title']);
});

test('POST /quotes returns 422 when a line item product_id is unknown', function () {
    $user = quoteApiUser();

    $this->withHeaders(quoteApiHeaders($user))
        ->postJson('/crm/api/v2/quotes', [
            'title' => 'Bad product',
            'line_items' => [
                [
                    'product_id' => (string) Str::uuid(),
                    'quantity' => 1,
                    'unit_price' => 10.00,
                    'amount' => 10.00,
                ],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['line_items.0.product_id']);
});

test('POST /quotes returns 403 when policy denies create', function () {
    $user = quoteApiUser(['crm_permissions' => json_encode([])]);

    $this->withHeaders(quoteApiHeaders($user))
        ->postJson('/crm/api/v2/quotes', ['title' => 'Forbidden'])
        ->assertStatus(403);
});

test('GET /quotes returns a paginated collection', function () {
    $user = quoteApiUser();

    foreach (range(1, 3) as $i) {
        Quote::create(['title' => 'Quote '.$i]);
    }

    $response = $this->withHeaders(quoteApiHeaders($user))
        ->getJson('/crm/api/v2/quotes?per_page=2');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
    expect($response->json('meta.total'))->toBe(3);
});

test('GET /quotes/{quote} returns the quote with line items', function () {
    $user = quoteApiUser();
    $product = createApiProduct();

    $created = $this->withHeaders(quoteApiHeaders($user))
        ->postJson('/crm/api/v2/quotes', [
            'title' => 'Find me',
            'line_items' => [[
                'product_id' => $product->external_id,
                'quantity' => 1,
                'unit_price' => 20.00,
                'amount' => 20.00,
            ]],
        ])->json('data');

    $response = $this->withHeaders(quoteApiHeaders($user))
        ->getJson('/crm/api/v2/quotes/'.$created['id']);

    $response->assertOk();
    expect($response->json('data.id'))->toBe($created['id']);
    expect($response->json('data.line_items'))->toHaveCount(1);
});

test('GET /quotes/{quote} returns 404 for unknown UUID', function () {
    $user = quoteApiUser();

    $this->withHeaders(quoteApiHeaders($user))
        ->getJson('/crm/api/v2/quotes/'.((string) Str::uuid()))
        ->assertStatus(404);
});

test('PUT /quotes/{quote} updates line items in place', function () {
    $user = quoteApiUser();
    $product = createApiProduct();

    $created = $this->withHeaders(quoteApiHeaders($user))
        ->postJson('/crm/api/v2/quotes', [
            'title' => 'Original',
            'line_items' => [[
                'product_id' => $product->external_id,
                'quantity' => 1,
                'unit_price' => 10.00,
                'amount' => 10.00,
            ]],
        ])->json('data');

    $lineItemId = $created['line_items'][0]['id'];

    $response = $this->withHeaders(quoteApiHeaders($user))
        ->putJson('/crm/api/v2/quotes/'.$created['id'], [
            'title' => 'Updated',
            'line_items' => [[
                'id' => $lineItemId,
                'product_id' => $product->external_id,
                'quantity' => 4,
                'unit_price' => 10.00,
                'amount' => 40.00,
            ]],
        ]);

    $response->assertOk();
    expect($response->json('data.title'))->toBe('Updated');
    expect($response->json('data.line_items'))->toHaveCount(1);
    expect($response->json('data.line_items.0.id'))->toBe($lineItemId);
    expect($response->json('data.line_items.0.quantity'))->toBe(4);

    // QuoteProduct row was updated, not duplicated.
    $stored = QuoteProduct::where('external_id', $lineItemId)->first();
    expect((int) $stored->quantity)->toBe(4);
    expect((int) $stored->amount)->toBe(4000);
});

test('DELETE /quotes/{quote} soft-deletes the quote', function () {
    $user = quoteApiUser();
    $quote = Quote::create(['title' => 'Toast']);

    $this->withHeaders(quoteApiHeaders($user))
        ->deleteJson('/crm/api/v2/quotes/'.$quote->external_id)
        ->assertStatus(204);

    expect(Quote::query()->where('external_id', $quote->external_id)->exists())->toBeFalse();
    expect(Quote::withTrashed()->where('external_id', $quote->external_id)->exists())->toBeTrue();
});

test('DELETE /quotes/{quote} returns 403 when policy denies delete', function () {
    $user = quoteApiUser(['crm_permissions' => json_encode([])]);
    $quote = Quote::create(['title' => 'Untouchable']);

    $this->withHeaders(quoteApiHeaders($user))
        ->deleteJson('/crm/api/v2/quotes/'.$quote->external_id)
        ->assertStatus(403);
});
