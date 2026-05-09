<?php

use Illuminate\Database\Eloquent\Relations\HasMany;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductCategory;

test('category uses prefixed table name', function () {
    expect((new ProductCategory)->getTable())->toBe('crm_product_categories');
});

test('creating a category persists a name', function () {
    $cat = ProductCategory::create(['name' => 'Tools']);
    expect($cat->fresh()->name)->toBe('Tools');
});

test('category has many products', function () {
    $cat = ProductCategory::create(['name' => 'Tools']);
    Product::create(['name' => 'Drill', 'product_category_id' => $cat->id]);
    Product::create(['name' => 'Saw', 'product_category_id' => $cat->id]);

    expect((new ProductCategory)->products())->toBeInstanceOf(HasMany::class);
    expect($cat->products()->count())->toBe(2);
});

test('category uses soft deletes', function () {
    $cat = ProductCategory::create(['name' => 'Bin']);
    $cat->delete();
    $this->assertSoftDeleted('crm_product_categories', ['id' => $cat->id]);
});
