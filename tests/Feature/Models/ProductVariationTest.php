<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\DealProduct;
use VentureDrake\LaravelCrm\Models\DeliveryProduct;
use VentureDrake\LaravelCrm\Models\OrderProduct;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductPrice;
use VentureDrake\LaravelCrm\Models\ProductVariation;
use VentureDrake\LaravelCrm\Models\QuoteProduct;

beforeEach(function () {
    app('laravel-crm.settings')->set('currency', 'USD');

    $this->product = Product::create(['name' => 'Widget', 'code' => 'WID']);
    $this->product->productPrices()->create(['unit_price' => 10, 'currency' => 'USD']);
});

function variationOf(Product $product, array $attributes = []): ProductVariation
{
    return $product->productVariations()->create(array_merge(['name' => 'Large'], $attributes));
}

test('variation uses prefixed table name', function () {
    expect((new ProductVariation)->getTable())->toBe('crm_product_variations');
});

test('the observer assigns a uuid and the acting user', function () {
    $user = $this->actingAsUser();

    $variation = variationOf($this->product);

    expect(Str::isUuid($variation->external_id))->toBeTrue();
    expect($variation->user_created_id)->toBe($user->id);

    $variation->update(['name' => 'Larger']);
    expect($variation->fresh()->user_updated_id)->toBe($user->id);

    $variation->delete();
    expect(ProductVariation::withTrashed()->find($variation->id)->user_deleted_id)->toBe($user->id);

    $variation->restore();
    expect($variation->fresh()->user_restored_id)->toBe($user->id);
    expect($variation->fresh()->user_deleted_id)->toBeNull();
});

test('active is cast to a boolean and defaults to true', function () {
    $variation = variationOf($this->product)->fresh();

    expect($variation->active)->toBeTrue();
});

test('active and ordered scopes', function () {
    variationOf($this->product, ['name' => 'C', 'order' => 3]);
    variationOf($this->product, ['name' => 'A', 'order' => 1]);
    variationOf($this->product, ['name' => 'Off', 'order' => 2, 'active' => false]);

    expect($this->product->productVariations()->pluck('name')->all())->toBe(['A', 'Off', 'C']);
    expect($this->product->activeProductVariations()->pluck('name')->all())->toBe(['A', 'C']);
});

test('getDefaultPrice and priceFor read the variation price by currency', function () {
    $variation = variationOf($this->product);
    $variation->productPrices()->create(['product_id' => $this->product->id, 'unit_price' => 15, 'currency' => 'USD']);
    $variation->productPrices()->create(['product_id' => $this->product->id, 'unit_price' => 12, 'currency' => 'GBP']);

    expect($variation->getDefaultPrice()->unit_price)->toBe(1500);
    expect($variation->priceFor('GBP')->unit_price)->toBe(1200);
    expect($variation->priceFor('EUR'))->toBeNull();

    // The eager-loaded path agrees with the query path.
    $loaded = ProductVariation::with('productPrices')->find($variation->id);
    expect($loaded->priceFor('GBP')->unit_price)->toBe(1200);
    expect($loaded->getDefaultPrice()->unit_price)->toBe(1500);
});

test('Product::getDefaultPrice ignores variation prices', function () {
    // A variation price in the install currency created *before* the product
    // price would otherwise be the first USD row on the product.
    $product = Product::create(['name' => 'Gadget']);
    $variation = variationOf($product);
    $variation->productPrices()->create(['product_id' => $product->id, 'unit_price' => 99, 'currency' => 'USD']);
    $product->productPrices()->create(['unit_price' => 20, 'currency' => 'USD']);

    expect($product->getDefaultPrice()->unit_price)->toBe(2000);
    expect(Product::with('productPrices')->find($product->id)->getDefaultPrice()->unit_price)->toBe(2000);
    expect($product->productPrices()->count())->toBe(1);
    expect(ProductPrice::default()->where('product_id', $product->id)->pluck('unit_price')->all())->toBe([2000]);
});

test('Product::getDefaultPrice is null when only a variation is priced', function () {
    $product = Product::create(['name' => 'Gizmo']);
    variationOf($product)->productPrices()->create(['product_id' => $product->id, 'unit_price' => 5, 'currency' => 'USD']);

    expect($product->getDefaultPrice())->toBeNull();
});

test('unitPriceFor prefers the variation price and falls back to the product price', function () {
    $priced = variationOf($this->product, ['name' => 'Priced']);
    $priced->productPrices()->create(['product_id' => $this->product->id, 'unit_price' => 15, 'currency' => 'USD']);
    $unpriced = variationOf($this->product, ['name' => 'Unpriced']);

    expect($this->product->unitPriceFor($priced, 'USD'))->toBe(1500);
    expect($this->product->unitPriceFor($unpriced, 'USD'))->toBe(1000);
    expect($this->product->unitPriceFor($priced, 'GBP'))->toBe(1000);
    expect($this->product->unitPriceFor(null, 'USD'))->toBe(1000);
});

test('line items print the product and variation name', function () {
    $variation = variationOf($this->product, ['code' => 'WID-L']);

    $withVariation = QuoteProduct::create(['quote_id' => 1, 'product_id' => $this->product->id, 'product_variation_id' => $variation->id]);
    $withoutVariation = DealProduct::create(['deal_id' => 1, 'product_id' => $this->product->id]);

    expect($withVariation->displayName())->toBe('Widget — Large');
    expect($withVariation->displayCode())->toBe('WID-L');
    expect($withoutVariation->displayName())->toBe('Widget');
    expect($withoutVariation->displayCode())->toBe('WID');
});

test('a line keeps its variation name after the variation is deleted', function () {
    $variation = variationOf($this->product);
    $line = OrderProduct::create(['order_id' => 1, 'product_id' => $this->product->id, 'product_variation_id' => $variation->id]);

    $variation->delete();

    expect($line->fresh()->displayName())->toBe('Widget — Large');
});

test('a variation without its own code prints the product code', function () {
    $variation = variationOf($this->product);
    $line = QuoteProduct::create(['quote_id' => 1, 'product_id' => $this->product->id, 'product_variation_id' => $variation->id]);

    expect($line->displayCode())->toBe('WID');
});

test('a delivery line prints the order line it draws down', function () {
    $variation = variationOf($this->product);
    $orderProduct = OrderProduct::create(['order_id' => 1, 'product_id' => $this->product->id, 'product_variation_id' => $variation->id]);
    $deliveryProduct = DeliveryProduct::create(['delivery_id' => 1, 'order_product_id' => $orderProduct->id]);

    expect($deliveryProduct->displayName())->toBe('Widget — Large');
    expect((new DeliveryProduct)->displayName())->toBe('');
});
