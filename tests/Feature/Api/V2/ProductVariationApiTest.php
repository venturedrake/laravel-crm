<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductVariation;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

function variationApiUser(array $attributes = []): User
{
    return User::create(array_merge([
        'name' => 'Variation API User',
        'email' => 'variation-api-'.uniqid().'@example.com',
        'password' => bcrypt('secret-password'),
        'crm_access' => true,
    ], $attributes));
}

function variationApiHeaders(User $user): array
{
    $token = $user->createToken('variation-api-test')->plainTextToken;

    return [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
    ];
}

function variationApiProduct(string $name = 'Widget', array $attributes = []): Product
{
    $product = Product::create(array_merge(['name' => $name], $attributes));
    $product->productPrices()->create(['unit_price' => 10.00, 'currency' => 'USD']);

    return $product->refresh();
}

function variationApiVariation(Product $product, string $name, ?float $unitPrice = null, array $attributes = []): ProductVariation
{
    $variation = $product->productVariations()->create(array_merge([
        'name' => $name,
        'order' => $product->productVariations()->count() + 1,
    ], $attributes));

    if ($unitPrice !== null) {
        $variation->productPrices()->create([
            'product_id' => $product->id,
            'unit_price' => $unitPrice,
            'currency' => 'USD',
        ]);
    }

    return $variation->refresh();
}

function variationsUrl(Product $product, ?ProductVariation $variation = null): string
{
    return '/crm/api/v2/products/'.$product->external_id.'/variations'
        .($variation ? '/'.$variation->external_id : '');
}

test('GET /products/{product} includes variations with their prices', function () {
    $user = variationApiUser();
    $product = variationApiProduct();
    variationApiVariation($product, 'Small');
    variationApiVariation($product, 'Large', 12.50, ['code' => 'WDG-L']);

    $response = $this->withHeaders(variationApiHeaders($user))
        ->getJson('/crm/api/v2/products/'.$product->external_id);

    $response->assertOk();

    // The product-level prices exclude the variation price rows.
    expect($response->json('data.prices'))->toHaveCount(1);
    expect($response->json('data.prices.0.unit_price'))->toEqual(10.00);

    $variations = $response->json('data.variations');
    expect($variations)->toHaveCount(2);
    expect($variations[0]['name'])->toBe('Small');
    expect($variations[0]['prices'])->toBe([]);
    expect($variations[1]['name'])->toBe('Large');
    expect($variations[1]['code'])->toBe('WDG-L');
    expect($variations[1]['active'])->toBeTrue();
    expect(Str::isUuid($variations[1]['id']))->toBeTrue();
    expect($variations[1]['prices'])->toBe([['currency' => 'USD', 'unit_price' => 12.5]]);
});

test('variation sub-resource supports index, store, show, update and destroy', function () {
    $user = variationApiUser();
    $product = variationApiProduct();
    variationApiVariation($product, 'Small');
    $headers = variationApiHeaders($user);

    $store = $this->withHeaders($headers)->postJson(variationsUrl($product), [
        'name' => 'Large',
        'code' => 'WDG-L',
        'barcode' => '0123456789',
        'unit_price' => 12.50,
    ]);

    $store->assertStatus(201);
    $id = $store->json('data.id');
    expect(Str::isUuid($id))->toBeTrue();
    expect($store->json('data.name'))->toBe('Large');
    expect($store->json('data.order'))->toBe(2);
    // Currency defaults to the product's price currency.
    expect($store->json('data.prices'))->toBe([['currency' => 'USD', 'unit_price' => 12.5]]);

    $variation = ProductVariation::where('external_id', $id)->first();
    expect((int) $variation->product_id)->toBe($product->id);
    expect((int) $variation->productPrices()->first()->unit_price)->toBe(1250);

    $index = $this->withHeaders($headers)->getJson(variationsUrl($product));
    $index->assertOk();
    expect(collect($index->json('data'))->pluck('name')->all())->toBe(['Small', 'Large']);

    $show = $this->withHeaders($headers)->getJson(variationsUrl($product, $variation));
    $show->assertOk();
    expect($show->json('data.id'))->toBe($id);
    expect($show->json('data.prices.0.unit_price'))->toEqual(12.50);

    // An update without unit_price leaves the price alone.
    $rename = $this->withHeaders($headers)->putJson(variationsUrl($product, $variation), [
        'name' => 'Extra large',
        'active' => false,
    ]);
    $rename->assertOk();
    expect($rename->json('data.name'))->toBe('Extra large');
    expect($rename->json('data.active'))->toBeFalse();
    expect($rename->json('data.code'))->toBe('WDG-L');
    expect($rename->json('data.prices.0.unit_price'))->toEqual(12.50);

    // An explicit null clears it; the product price then applies.
    $clear = $this->withHeaders($headers)->putJson(variationsUrl($product, $variation), [
        'unit_price' => null,
    ]);
    $clear->assertOk();
    expect($clear->json('data.prices'))->toBe([]);
    expect($variation->productPrices()->count())->toBe(0);

    $destroy = $this->withHeaders($headers)->deleteJson(variationsUrl($product, $variation));
    $destroy->assertStatus(204);

    expect(ProductVariation::query()->whereKey($variation->id)->exists())->toBeFalse();
    expect(ProductVariation::withTrashed()->whereKey($variation->id)->exists())->toBeTrue();

    $this->withHeaders($headers)->getJson(variationsUrl($product, $variation))->assertStatus(404);
});

test('POST /products/{product}/variations returns 422 when name is missing', function () {
    $user = variationApiUser();
    $product = variationApiProduct();

    $this->withHeaders(variationApiHeaders($user))
        ->postJson(variationsUrl($product), ['unit_price' => 'cheap'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'unit_price']);
});

test('variation writes require edit on the product, reads require view', function () {
    $product = variationApiProduct();
    $variation = variationApiVariation($product, 'Small');

    $viewer = variationApiUser(['crm_permissions' => json_encode(['view crm products'])]);
    $headers = variationApiHeaders($viewer);

    $this->withHeaders($headers)->getJson(variationsUrl($product))->assertOk();
    $this->withHeaders($headers)->postJson(variationsUrl($product), ['name' => 'Large'])->assertStatus(403);
    $this->withHeaders($headers)->putJson(variationsUrl($product, $variation), ['name' => 'Tiny'])->assertStatus(403);
    $this->withHeaders($headers)->deleteJson(variationsUrl($product, $variation))->assertStatus(403);

    expect($product->productVariations()->count())->toBe(1);
    expect($variation->fresh()->name)->toBe('Small');
});

test('variation reads return 403 without view on the product', function () {
    $product = variationApiProduct();
    $variation = variationApiVariation($product, 'Small');

    $nobody = variationApiUser(['crm_permissions' => json_encode([])]);
    $headers = variationApiHeaders($nobody);

    $this->withHeaders($headers)->getJson(variationsUrl($product))->assertStatus(403);
    $this->withHeaders($headers)->getJson(variationsUrl($product, $variation))->assertStatus(403);
});

test('a variation of another product is a 404 under the URL product', function () {
    $user = variationApiUser();
    $product = variationApiProduct('Widget');
    $other = variationApiProduct('Gadget');
    $otherVariation = variationApiVariation($other, 'Blue');

    $headers = variationApiHeaders($user);

    $this->withHeaders($headers)->getJson(variationsUrl($product, $otherVariation))->assertStatus(404);
    $this->withHeaders($headers)->putJson(variationsUrl($product, $otherVariation), ['name' => 'Red'])->assertStatus(404);
    $this->withHeaders($headers)->deleteJson(variationsUrl($product, $otherVariation))->assertStatus(404);

    expect($otherVariation->fresh()->name)->toBe('Blue');
    expect($otherVariation->fresh()->trashed())->toBeFalse();
});

test('PUT /products/{product} without variations leaves variations untouched', function () {
    $user = variationApiUser();
    $product = variationApiProduct();
    $small = variationApiVariation($product, 'Small');
    $large = variationApiVariation($product, 'Large', 12.50);

    $response = $this->withHeaders(variationApiHeaders($user))
        ->patchJson('/crm/api/v2/products/'.$product->external_id, [
            'name' => 'Renamed widget',
            'unit_price' => 11.00,
        ]);

    $response->assertOk();
    expect($response->json('data.variations'))->toHaveCount(2);

    $product->refresh();
    expect($product->name)->toBe('Renamed widget');
    expect($product->productVariations()->pluck('name')->all())->toBe(['Small', 'Large']);
    expect($small->fresh()->trashed())->toBeFalse();
    expect((int) $large->productPrices()->first()->unit_price)->toBe(1250);

    // The product's own price was updated, not the variation's.
    expect((int) $product->productPrices()->first()->unit_price)->toBe(1100);
    expect($product->productPrices()->count())->toBe(1);
});

test('POST /quotes rejects a line item variation of a different product', function () {
    $user = variationApiUser();
    $product = variationApiProduct('Widget');
    $other = variationApiProduct('Gadget');
    $otherVariation = variationApiVariation($other, 'Blue');

    $this->withHeaders(variationApiHeaders($user))
        ->postJson('/crm/api/v2/quotes', [
            'title' => 'Mismatched variation',
            'line_items' => [
                [
                    'product_id' => $product->external_id,
                    'product_variation_id' => $otherVariation->external_id,
                    'quantity' => 1,
                    'unit_price' => 10.00,
                    'amount' => 10.00,
                ],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['line_items.0.product_variation_id']);

    expect(Quote::count())->toBe(0);
});

test('POST /quotes stores a line item variation and returns it on the line', function () {
    $user = variationApiUser();
    $product = variationApiProduct('Widget');
    $large = variationApiVariation($product, 'Large', 12.50, ['code' => 'WDG-L']);

    $response = $this->withHeaders(variationApiHeaders($user))
        ->postJson('/crm/api/v2/quotes', [
            'title' => 'Variation quote',
            'line_items' => [
                [
                    'product_id' => $product->external_id,
                    'product_variation_id' => $large->external_id,
                    'quantity' => 2,
                    'unit_price' => 12.50,
                    'amount' => 25.00,
                ],
                [
                    'product_id' => $product->external_id,
                    'quantity' => 1,
                    'unit_price' => 10.00,
                    'amount' => 10.00,
                ],
            ],
        ]);

    $response->assertStatus(201);

    $lines = $response->json('data.line_items');
    expect($lines[0]['product_variation_id'])->toBe($large->external_id);
    expect($lines[0]['variation'])->toBe([
        'id' => $large->external_id,
        'name' => 'Large',
        'code' => 'WDG-L',
    ]);
    expect($lines[1]['product_variation_id'])->toBeNull();
    expect($lines[1]['variation'])->toBeNull();

    $quote = Quote::where('external_id', $response->json('data.id'))->first();
    expect((int) $quote->quoteProducts()->orderBy('order')->first()->product_variation_id)->toBe($large->id);

    // Re-sending the line by id keeps the variation; show returns it too.
    $lineId = $lines[0]['id'];
    $update = $this->withHeaders(variationApiHeaders($user))
        ->putJson('/crm/api/v2/quotes/'.$quote->external_id, [
            'line_items' => [
                [
                    'id' => $lineId,
                    'product_id' => $product->external_id,
                    'product_variation_id' => $large->external_id,
                    'quantity' => 3,
                    'unit_price' => 12.50,
                    'amount' => 37.50,
                ],
            ],
        ]);

    $update->assertOk();
    expect($update->json('data.line_items'))->toHaveCount(1);
    expect($update->json('data.line_items.0.id'))->toBe($lineId);
    expect($update->json('data.line_items.0.variation.name'))->toBe('Large');
});

test('a line item variation from another team is a 422', function () {
    config()->set('laravel-crm.teams', true);

    try {
        $user = variationApiUser([
            'current_team_id' => 1,
            'team_ids' => json_encode([1]),
        ]);

        $product = variationApiProduct('Widget', ['team_id' => 1]);
        $otherTeamProduct = variationApiProduct('Gadget', ['team_id' => 2]);
        $otherTeamVariation = variationApiVariation($otherTeamProduct, 'Blue', null, ['team_id' => 2]);

        $this->withHeaders(variationApiHeaders($user))
            ->postJson('/crm/api/v2/quotes', [
                'title' => 'Cross-team variation',
                'line_items' => [
                    [
                        'product_id' => $product->external_id,
                        'product_variation_id' => $otherTeamVariation->external_id,
                        'quantity' => 1,
                        'unit_price' => 10.00,
                        'amount' => 10.00,
                    ],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['line_items.0.product_variation_id']);
    } finally {
        config()->set('laravel-crm.teams', false);
    }
});
