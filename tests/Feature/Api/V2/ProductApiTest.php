<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductCategory;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

function productApiUser(array $attributes = []): User
{
    return User::create(array_merge([
        'name' => 'Product API User',
        'email' => 'product-api-'.uniqid().'@example.com',
        'password' => bcrypt('secret-password'),
        'crm_access' => true,
    ], $attributes));
}

function productApiHeaders(User $user): array
{
    $token = $user->createToken('product-api-test')->plainTextToken;

    return [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
    ];
}

test('GET /products returns 401 when unauthenticated', function () {
    $response = $this->getJson('/api/crm/v2/products');

    $response->assertStatus(401);
});

test('POST /products returns 401 when unauthenticated', function () {
    $response = $this->postJson('/api/crm/v2/products', ['name' => 'Hello']);

    $response->assertStatus(401);
});

test('GET /products returns paginated collection with UUID ids and ISO timestamps', function () {
    $user = productApiUser();

    foreach (range(1, 3) as $i) {
        Product::create(['name' => 'Widget '.$i]);
    }

    $response = $this->withHeaders(productApiHeaders($user))
        ->getJson('/api/crm/v2/products');

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            '*' => ['id', 'name', 'code', 'created_at', 'updated_at'],
        ],
        'links',
        'meta' => ['current_page', 'per_page', 'total'],
    ]);

    $payload = $response->json();
    expect($payload['data'])->toHaveCount(3);
    expect(Str::isUuid($payload['data'][0]['id']))->toBeTrue();
    expect($payload['data'][0]['created_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
});

test('GET /products honors per_page pagination', function () {
    $user = productApiUser();

    foreach (range(1, 5) as $i) {
        Product::create(['name' => 'Widget '.$i]);
    }

    $response = $this->withHeaders(productApiHeaders($user))
        ->getJson('/api/crm/v2/products?per_page=2');

    $response->assertOk();
    $payload = $response->json();

    expect($payload['data'])->toHaveCount(2);
    expect($payload['meta']['per_page'])->toBe(2);
    expect($payload['meta']['total'])->toBe(5);
});

test('GET /products filters by user_owner_id', function () {
    $user = productApiUser();
    $otherOwner = productApiUser();

    Product::create(['name' => 'Mine', 'user_owner_id' => $user->id]);
    Product::create(['name' => 'Mine 2', 'user_owner_id' => $user->id]);
    Product::create(['name' => 'Theirs', 'user_owner_id' => $otherOwner->id]);

    $response = $this->withHeaders(productApiHeaders($user))
        ->getJson('/api/crm/v2/products?user_owner_id='.$user->id);

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(2);
});

test('POST /products creates a product and returns 201 with UUID id and divided unit_price', function () {
    $user = productApiUser();

    $response = $this->withHeaders(productApiHeaders($user))
        ->postJson('/api/crm/v2/products', [
            'name' => 'Premium Widget',
            'code' => 'WDG-1',
            'description' => 'A premium widget',
            'unit_price' => 19.95,
            'currency' => 'USD',
        ]);

    $response->assertStatus(201);
    $payload = $response->json('data');

    expect($payload['name'])->toBe('Premium Widget');
    expect($payload['code'])->toBe('WDG-1');
    expect(Str::isUuid($payload['id']))->toBeTrue();

    expect($payload['prices'])->toHaveCount(1);
    expect($payload['prices'][0]['unit_price'])->toEqual(19.95);
    expect($payload['prices'][0]['currency'])->toBe('USD');

    // Raw DB unit_price stored as integer cents.
    $stored = Product::where('external_id', $payload['id'])->first();
    expect((int) $stored->productPrices()->first()->unit_price)->toBe(1995);
});

test('POST /products resolves UUID for product_category_id', function () {
    $user = productApiUser();
    $category = ProductCategory::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Hardware',
    ]);

    $response = $this->withHeaders(productApiHeaders($user))
        ->postJson('/api/crm/v2/products', [
            'name' => 'Categorised Widget',
            'product_category_id' => $category->external_id,
            'unit_price' => 5,
        ]);

    $response->assertStatus(201);

    $stored = Product::where('external_id', $response->json('data.id'))->first();
    expect((int) $stored->product_category_id)->toBe($category->id);
    expect($response->json('data.product_category_id'))->toBe($category->external_id);
});

test('POST /products returns 422 when name is missing', function () {
    $user = productApiUser();

    $response = $this->withHeaders(productApiHeaders($user))
        ->postJson('/api/crm/v2/products', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name']);
});

test('POST /products returns 422 when product_category_id is not a UUID', function () {
    $user = productApiUser();

    $response = $this->withHeaders(productApiHeaders($user))
        ->postJson('/api/crm/v2/products', [
            'name' => 'Bad data',
            'product_category_id' => 'not-a-uuid',
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['product_category_id']);
});

test('POST /products returns 422 when product_category UUID does not exist', function () {
    $user = productApiUser();

    $response = $this->withHeaders(productApiHeaders($user))
        ->postJson('/api/crm/v2/products', [
            'name' => 'Missing category',
            'product_category_id' => (string) Str::uuid(),
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['product_category_id']);
});

test('POST /products returns 422 when unit_price is not numeric', function () {
    $user = productApiUser();

    $response = $this->withHeaders(productApiHeaders($user))
        ->postJson('/api/crm/v2/products', [
            'name' => 'Bad price',
            'unit_price' => 'twenty bucks',
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['unit_price']);
});

test('POST /products returns 403 when policy denies create', function () {
    $user = productApiUser(['crm_permissions' => json_encode([])]);

    $response = $this->withHeaders(productApiHeaders($user))
        ->postJson('/api/crm/v2/products', [
            'name' => 'Forbidden',
        ]);

    $response->assertStatus(403);
});

test('GET /products/{product} returns 403 when policy denies view', function () {
    $user = productApiUser(['crm_permissions' => json_encode([])]);
    $product = Product::create(['name' => 'Hidden']);

    $response = $this->withHeaders(productApiHeaders($user))
        ->getJson('/api/crm/v2/products/'.$product->external_id);

    $response->assertStatus(403);
});

test('GET /products/{product} returns the product by UUID', function () {
    $user = productApiUser();
    $product = Product::create(['name' => 'Findable']);

    $response = $this->withHeaders(productApiHeaders($user))
        ->getJson('/api/crm/v2/products/'.$product->external_id);

    $response->assertOk();
    expect($response->json('data.id'))->toBe($product->external_id);
    expect($response->json('data.name'))->toBe('Findable');
});

test('GET /products/{product} returns 404 for unknown UUID', function () {
    $user = productApiUser();

    $response = $this->withHeaders(productApiHeaders($user))
        ->getJson('/api/crm/v2/products/'.((string) Str::uuid()));

    $response->assertStatus(404);
});

test('PUT /products/{product} updates a product', function () {
    $user = productApiUser();
    $product = Product::create(['name' => 'Original']);
    $product->productPrices()->create(['unit_price' => 1000, 'currency' => 'USD']);

    $response = $this->withHeaders(productApiHeaders($user))
        ->putJson('/api/crm/v2/products/'.$product->external_id, [
            'name' => 'Updated',
            'unit_price' => 25.75,
        ]);

    $response->assertOk();
    expect($response->json('data.name'))->toBe('Updated');

    $fresh = $product->fresh();
    expect($fresh->name)->toBe('Updated');
    expect((int) $fresh->productPrices()->first()->unit_price)->toBe(2575);
});

test('DELETE /products/{product} soft-deletes the product', function () {
    $user = productApiUser();
    $product = Product::create(['name' => 'Toast']);

    $response = $this->withHeaders(productApiHeaders($user))
        ->deleteJson('/api/crm/v2/products/'.$product->external_id);

    $response->assertStatus(204);

    expect(Product::query()->where('external_id', $product->external_id)->exists())->toBeFalse();
    expect(Product::withTrashed()->where('external_id', $product->external_id)->exists())->toBeTrue();

    $followUp = $this->withHeaders(productApiHeaders($user))
        ->getJson('/api/crm/v2/products/'.$product->external_id);
    $followUp->assertStatus(404);
});

test('DELETE /products/{product} returns 403 when policy denies delete', function () {
    $user = productApiUser(['crm_permissions' => json_encode([])]);
    $product = Product::create(['name' => 'Untouchable']);

    $response = $this->withHeaders(productApiHeaders($user))
        ->deleteJson('/api/crm/v2/products/'.$product->external_id);

    $response->assertStatus(403);
    expect(Product::query()->where('external_id', $product->external_id)->exists())->toBeTrue();
});
