<?php

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductCategory;
use VentureDrake\LaravelCrm\Models\TaxRate;

test('product uses prefixed table name', function () {
    expect((new Product)->getTable())->toBe('crm_products');
});

test('creating a product assigns uuid', function () {
    $product = Product::create(['name' => 'Widget']);
    expect(Str::isUuid($product->external_id))->toBeTrue();
});

test('product observer assigns default tax rate', function () {
    $tax = TaxRate::create(['name' => 'GST', 'rate' => 10, 'default' => true]);
    $product = Product::create(['name' => 'Taxable']);

    expect($product->tax_rate_id)->toBe($tax->id);
    expect($product->tax_rate)->toEqual(10);
});

test('product observer falls back to setting tax rate', function () {
    app('laravel-crm.settings')->set('tax_rate', '7.5');
    $product = Product::create(['name' => 'No-default']);

    expect($product->tax_rate)->toEqual(7.5);
});

test('product belongs to category', function () {
    $cat = ProductCategory::create(['name' => 'Hardware']);
    $product = Product::create(['name' => 'Hammer', 'product_category_id' => $cat->id]);

    expect($product->productCategory->is($cat))->toBeTrue();
});

test('product relationships are defined', function () {
    $product = new Product;

    expect($product->productPrices())->toBeInstanceOf(HasMany::class);
    expect($product->productVariations())->toBeInstanceOf(HasMany::class);
    expect($product->productCategory())->toBeInstanceOf(BelongsTo::class);
    expect($product->taxRate())->toBeInstanceOf(BelongsTo::class);
});

test('active scope filters inactive products', function () {
    Product::create(['name' => 'On', 'active' => true]);
    Product::create(['name' => 'Off', 'active' => false]);

    expect(Product::active()->count())->toBe(1);
});

test('product uses soft deletes', function () {
    $product = Product::create(['name' => 'Bin']);
    $product->delete();
    $this->assertSoftDeleted('crm_products', ['id' => $product->id]);
});
