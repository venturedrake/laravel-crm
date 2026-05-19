<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\OrderProduct;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

function orderApiUser(array $attributes = []): User
{
    return User::create(array_merge([
        'name' => 'Order API User',
        'email' => 'order-api-'.uniqid().'@example.com',
        'password' => bcrypt('secret-password'),
        'crm_access' => true,
    ], $attributes));
}

function orderApiHeaders(User $user): array
{
    $token = $user->createToken('order-api-test')->plainTextToken;

    return [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
    ];
}

function createOrderApiProduct(string $name = 'Widget'): Product
{
    return Product::create(['name' => $name])->refresh();
}

test('GET /orders returns 401 when unauthenticated', function () {
    $this->getJson('/api/crm/v2/orders')->assertStatus(401);
});

test('POST /orders creates an order with nested line items', function () {
    $user = orderApiUser();
    $organization = Organization::create(['name' => 'Acme Co']);
    $product = createOrderApiProduct();

    $response = $this->withHeaders(orderApiHeaders($user))
        ->postJson('/api/crm/v2/orders', [
            'reference' => 'O-REF-1',
            'description' => 'First order',
            'currency' => 'USD',
            'subtotal' => 200.00,
            'tax' => 20.00,
            'total' => 220.00,
            'organization_id' => $organization->external_id,
            'user_owner_id' => $user->id,
            'line_items' => [
                [
                    'product_id' => $product->external_id,
                    'quantity' => 4,
                    'unit_price' => 50.00,
                    'amount' => 200.00,
                    'comments' => 'Rush',
                ],
            ],
        ]);

    $response->assertStatus(201);

    $payload = $response->json('data');
    expect(Str::isUuid($payload['id']))->toBeTrue();
    expect($payload['reference'])->toBe('O-REF-1');
    expect($payload['total'])->toEqual(220.00);
    expect($payload['line_items'])->toHaveCount(1);
    expect($payload['line_items'][0]['product_id'])->toBe($product->external_id);
    expect($payload['line_items'][0]['quantity'])->toBe(4);
    expect($payload['line_items'][0]['unit_price'])->toEqual(50.00);
    expect($payload['line_items'][0]['amount'])->toEqual(200.00);

    $stored = Order::where('external_id', $payload['id'])->first();
    expect((int) $stored->total)->toBe(22000);
    expect($stored->orderProducts)->toHaveCount(1);
    expect((int) $stored->orderProducts->first()->amount)->toBe(20000);
});

test('POST /orders returns 422 when a line item is missing required fields', function () {
    $user = orderApiUser();
    $product = createOrderApiProduct();

    $this->withHeaders(orderApiHeaders($user))
        ->postJson('/api/crm/v2/orders', [
            'line_items' => [[
                'product_id' => $product->external_id,
                // missing quantity, unit_price, amount
            ]],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'line_items.0.quantity',
            'line_items.0.unit_price',
            'line_items.0.amount',
        ]);
});

test('POST /orders returns 403 when policy denies create', function () {
    $user = orderApiUser(['crm_permissions' => json_encode([])]);

    $this->withHeaders(orderApiHeaders($user))
        ->postJson('/api/crm/v2/orders', [])
        ->assertStatus(403);
});

test('GET /orders honors per_page pagination', function () {
    $user = orderApiUser();

    foreach (range(1, 4) as $i) {
        Order::create(['description' => 'Order '.$i]);
    }

    $response = $this->withHeaders(orderApiHeaders($user))
        ->getJson('/api/crm/v2/orders?per_page=2');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
    expect($response->json('meta.total'))->toBe(4);
});

test('GET /orders/{order} returns 404 for unknown UUID', function () {
    $user = orderApiUser();

    $this->withHeaders(orderApiHeaders($user))
        ->getJson('/api/crm/v2/orders/'.((string) Str::uuid()))
        ->assertStatus(404);
});

test('PUT /orders/{order} replaces line items', function () {
    $user = orderApiUser();
    $productA = createOrderApiProduct('A');
    $productB = createOrderApiProduct('B');

    $created = $this->withHeaders(orderApiHeaders($user))
        ->postJson('/api/crm/v2/orders', [
            'line_items' => [[
                'product_id' => $productA->external_id,
                'quantity' => 1,
                'unit_price' => 10.00,
                'amount' => 10.00,
            ]],
        ])->json('data');

    // Replace with a new line; original line should be removed since no id is provided.
    $response = $this->withHeaders(orderApiHeaders($user))
        ->putJson('/api/crm/v2/orders/'.$created['id'], [
            'line_items' => [[
                'product_id' => $productB->external_id,
                'quantity' => 2,
                'unit_price' => 25.00,
                'amount' => 50.00,
            ]],
        ]);

    $response->assertOk();
    expect($response->json('data.line_items'))->toHaveCount(1);
    expect($response->json('data.line_items.0.product_id'))->toBe($productB->external_id);

    expect(OrderProduct::query()->where('order_id', Order::where('external_id', $created['id'])->value('id'))->count())->toBe(1);
});

test('DELETE /orders/{order} soft-deletes the order', function () {
    $user = orderApiUser();
    $order = Order::create(['description' => 'Toast']);

    $this->withHeaders(orderApiHeaders($user))
        ->deleteJson('/api/crm/v2/orders/'.$order->external_id)
        ->assertStatus(204);

    expect(Order::query()->where('external_id', $order->external_id)->exists())->toBeFalse();
    expect(Order::withTrashed()->where('external_id', $order->external_id)->exists())->toBeTrue();
});
