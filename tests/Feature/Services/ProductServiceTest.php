<?php

use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductCategory;
use VentureDrake\LaravelCrm\Services\ProductService;

beforeEach(function () {
    app('laravel-crm.settings')->set('currency', 'USD');
});

test('service creates a product with minimum data', function () {
    $product = app(ProductService::class)->create(new Request([
        'name' => 'Widget', 'code' => 'W-1', 'unit' => 'each', 'currency' => 'USD', 'unit_price' => 10,
    ]));

    expect($product)->toBeInstanceOf(Product::class);
    expect($product->name)->toBe('Widget');
    expect($product->code)->toBe('W-1');
    expect($product->productPrices()->count())->toBe(1);
    expect((int) $product->productPrices()->first()->getRawOriginal('unit_price'))->toBe(1000);
});

test('service attaches category', function () {
    $cat = ProductCategory::create(['name' => 'Cat']);

    $product = app(ProductService::class)->create(new Request([
        'name' => 'P', 'product_category' => $cat->id, 'currency' => 'USD', 'unit_price' => 100,
    ]));

    expect($product->product_category_id)->toBe($cat->id);
});

test('service updates a product', function () {
    $product = Product::create(['name' => 'Original']);

    app(ProductService::class)->update($product, new Request([
        'name' => 'Renamed', 'description' => 'Updated description', 'currency' => 'USD', 'unit_price' => 999,
    ]));

    $fresh = $product->fresh();
    expect($fresh->name)->toBe('Renamed');
    expect($fresh->description)->toBe('Updated description');
});
