<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductCategory;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Tests\TestCase;

class ProductTest extends TestCase
{
    public function test_product_uses_prefixed_table_name(): void
    {
        $this->assertSame('crm_products', (new Product)->getTable());
    }

    public function test_creating_a_product_assigns_uuid(): void
    {
        $product = Product::create(['name' => 'Widget']);
        $this->assertTrue(Str::isUuid($product->external_id));
    }

    public function test_product_observer_assigns_default_tax_rate(): void
    {
        $tax = TaxRate::create(['name' => 'GST', 'rate' => 10, 'default' => true]);

        $product = Product::create(['name' => 'Taxable']);

        $this->assertSame($tax->id, (int) $product->tax_rate_id);
        $this->assertEquals(10, $product->tax_rate);
    }

    public function test_product_observer_falls_back_to_setting_tax_rate(): void
    {
        app('laravel-crm.settings')->set('tax_rate', '7.5');

        $product = Product::create(['name' => 'No-default']);

        $this->assertEquals(7.5, $product->tax_rate);
    }

    public function test_product_belongs_to_category(): void
    {
        $cat = ProductCategory::create(['name' => 'Hardware']);
        $product = Product::create(['name' => 'Hammer', 'product_category_id' => $cat->id]);

        $this->assertTrue($product->productCategory->is($cat));
    }

    public function test_product_relationships_are_defined(): void
    {
        $product = new Product;

        $this->assertInstanceOf(HasMany::class, $product->productPrices());
        $this->assertInstanceOf(HasMany::class, $product->productVariations());
        $this->assertInstanceOf(BelongsTo::class, $product->productCategory());
        $this->assertInstanceOf(BelongsTo::class, $product->taxRate());
    }

    public function test_active_scope_filters_inactive_products(): void
    {
        Product::create(['name' => 'On', 'active' => true]);
        Product::create(['name' => 'Off', 'active' => false]);

        $this->assertSame(1, Product::active()->count());
    }

    public function test_product_uses_soft_deletes(): void
    {
        $product = Product::create(['name' => 'Bin']);
        $product->delete();
        $this->assertSoftDeleted('crm_products', ['id' => $product->id]);
    }

    public function test_product_is_auditable(): void
    {
        $this->assertInstanceOf(Auditable::class, new Product);
    }
}

