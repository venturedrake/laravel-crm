<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Services;

use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductCategory;
use VentureDrake\LaravelCrm\Services\ProductService;
use VentureDrake\LaravelCrm\Tests\TestCase;

class ProductServiceTest extends TestCase
{
    private function service(): ProductService
    {
        return app(ProductService::class);
    }

    private function request(array $attributes): Request
    {
        return new Request($attributes);
    }

    protected function setUp(): void
    {
        parent::setUp();
        // ProductService::update() calls Product::getDefaultPrice() which uses
        // Setting::currency()->value — make sure that setting exists.
        app('laravel-crm.settings')->set('currency', 'USD');
    }

    public function test_service_creates_a_product_with_minimum_data(): void
    {
        $product = $this->service()->create($this->request([
            'name' => 'Widget',
            'code' => 'W-1',
            'unit' => 'each',
            'currency' => 'USD',
            'unit_price' => 10,
        ]));

        $this->assertInstanceOf(Product::class, $product);
        $this->assertSame('Widget', $product->name);
        $this->assertSame('W-1', $product->code);
        $this->assertSame(1, $product->productPrices()->count());
        // setUnitPriceAttribute multiplies by 100, so 10 → 1000 (cents).
        $this->assertSame(1000, (int) $product->productPrices()->first()->getRawOriginal('unit_price'));
    }

    public function test_service_attaches_category(): void
    {
        $cat = ProductCategory::create(['name' => 'Cat']);

        $product = $this->service()->create($this->request([
            'name' => 'P',
            'product_category' => $cat->id,
            'currency' => 'USD',
            'unit_price' => 100,
        ]));

        $this->assertSame($cat->id, $product->product_category_id);
    }

    public function test_service_updates_a_product(): void
    {
        $product = Product::create(['name' => 'Original']);

        $this->service()->update($product, $this->request([
            'name' => 'Renamed',
            'description' => 'Updated description',
            'currency' => 'USD',
            'unit_price' => 999,
        ]));

        $fresh = $product->fresh();
        $this->assertSame('Renamed', $fresh->name);
        $this->assertSame('Updated description', $fresh->description);
    }
}
