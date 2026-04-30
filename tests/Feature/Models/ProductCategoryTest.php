<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductCategory;
use VentureDrake\LaravelCrm\Tests\TestCase;

class ProductCategoryTest extends TestCase
{
    public function test_category_uses_prefixed_table_name(): void
    {
        $this->assertSame('crm_product_categories', (new ProductCategory)->getTable());
    }

    public function test_creating_a_category_persists_a_name(): void
    {
        $cat = ProductCategory::create(['name' => 'Tools']);
        $this->assertSame('Tools', $cat->fresh()->name);
    }

    public function test_category_has_many_products(): void
    {
        $cat = ProductCategory::create(['name' => 'Tools']);
        Product::create(['name' => 'Drill', 'product_category_id' => $cat->id]);
        Product::create(['name' => 'Saw', 'product_category_id' => $cat->id]);

        $this->assertInstanceOf(HasMany::class, (new ProductCategory)->products());
        $this->assertSame(2, $cat->products()->count());
    }

    public function test_category_uses_soft_deletes(): void
    {
        $cat = ProductCategory::create(['name' => 'Bin']);
        $cat->delete();
        $this->assertSoftDeleted('crm_product_categories', ['id' => $cat->id]);
    }
}
