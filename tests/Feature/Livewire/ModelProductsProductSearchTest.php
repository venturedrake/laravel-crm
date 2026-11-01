<?php

/*
 * The product picker on a line item row - crm-model-products (quote, order,
 * invoice, purchase order) and the deal form.
 *
 * It used to render every product into every row on every round trip, which
 * stalled the form on a catalogue of thousands. The options are now searched
 * on the server, capped at 20, and always include the row's own product.
 */

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Deals\DealCreate;
use VentureDrake\LaravelCrm\Livewire\ModelProducts;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Setting;

/**
 * Thirty products named "Product 01" .. "Product 30", so the cap of 20 drops
 * "Product 21" onwards from the unsearched list.
 *
 * @return array<int, Product>
 */
function productSearchCatalogue(): array
{
    $products = [];

    foreach (range(1, 30) as $number) {
        $products[$number] = Product::create([
            'name' => sprintf('Product %02d', $number),
            'code' => sprintf('P-%02d', $number),
        ]);
    }

    return $products;
}

function productSearchOptionIds(array $options): array
{
    return array_column($options, 'id');
}

beforeEach(function () {
    $this->actingAsUser();
});

test('a row offers at most 20 products and does not render the rest', function () {
    $products = productSearchCatalogue();

    $component = Livewire::test(ModelProducts::class);
    $rowKey = $component->get('products.0.row_key');

    $options = $component->instance()->productOptions($rowKey);

    expect($options)->toHaveCount(20)
        ->and(productSearchOptionIds($options))->toContain($products[1]->id)
        ->and(productSearchOptionIds($options))->not->toContain($products[30]->id);

    $component->assertSee('Product 01')->assertDontSee('Product 30');
});

test('searching a row narrows it by name and by code', function () {
    productSearchCatalogue();
    $rose = Product::create(['name' => 'Rose Bush', 'code' => 'RB-1']);
    $tulip = Product::create(['name' => 'Tulip', 'code' => 'BULB-ROSE']);

    $component = Livewire::test(ModelProducts::class);
    $rowKey = $component->get('products.0.row_key');

    $component->call('searchProducts', 'rose', $rowKey);

    expect(productSearchOptionIds($component->instance()->productOptions($rowKey)))
        ->toEqualCanonicalizing([$rose->id, $tulip->id]);

    $component->call('searchProducts', 'RB-1', $rowKey);

    expect(productSearchOptionIds($component->instance()->productOptions($rowKey)))->toBe([$rose->id]);
});

test('searching one row leaves the other rows alone', function () {
    productSearchCatalogue();
    $rose = Product::create(['name' => 'Rose Bush']);

    $component = Livewire::test(ModelProducts::class)->call('add');
    $first = $component->get('products.0.row_key');
    $second = $component->get('products.1.row_key');

    $component->call('searchProducts', 'Rose', $first);

    expect(productSearchOptionIds($component->instance()->productOptions($first)))->toBe([$rose->id])
        ->and($component->instance()->productOptions($second))->toHaveCount(20);
});

test('a row keeps its product in its options when it falls outside the matches', function () {
    $products = productSearchCatalogue();

    $component = Livewire::test(ModelProducts::class)
        ->set('products.0.id', $products[30]->id);
    $rowKey = $component->get('products.0.row_key');

    expect(productSearchOptionIds($component->instance()->productOptions($rowKey, $products[30]->id)))
        ->toContain($products[30]->id);

    $component->assertSee('Product 30');
});

test('an existing line on a deleted product keeps its label', function () {
    $product = Product::create(['name' => 'Retired Fern']);

    $quote = Quote::create(['title' => 'Quote', 'currency' => 'USD']);
    $quote->quoteProducts()->create([
        'product_id' => $product->id, 'quantity' => 1, 'price' => 500, 'amount' => 500,
    ]);

    $product->delete();

    $component = Livewire::test(ModelProducts::class, ['model' => $quote]);
    $rowKey = $component->get('products.0.row_key');

    expect($component->instance()->productOptions($rowKey, $product->id))
        ->toContain(['id' => $product->id, 'name' => 'Retired Fern', 'code' => null]);

    $component->assertSee('Retired Fern');
});

test('picking a product clears the row search', function () {
    $rose = Product::create(['name' => 'Rose Bush']);

    $component = Livewire::test(ModelProducts::class);
    $rowKey = $component->get('products.0.row_key');

    $component->call('searchProducts', 'Rose', $rowKey)
        ->assertSet("productSearch.$rowKey", 'Rose')
        ->set('products.0.id', $rose->id);

    expect($component->get('productSearch'))->not->toHaveKey($rowKey);
});

test('removing a row drops its search', function () {
    $component = Livewire::test(ModelProducts::class)->call('add');
    $rowKey = $component->get('products.0.row_key');

    $component->call('searchProducts', 'Rose', $rowKey)
        ->call('remove', 0);

    expect($component->get('productSearch'))->toBe([]);
});

test('the deal form caps and searches its product options and keeps no catalogue in its state', function () {
    Setting::create(['name' => 'currency', 'value' => 'USD']);
    Pipeline::create(['name' => 'Deals', 'model' => Deal::class]);

    $products = productSearchCatalogue();
    $rose = Product::create(['name' => 'Rose Bush']);

    $component = Livewire::test(DealCreate::class);

    expect($component->instance()->productOptions(0))->toHaveCount(20)
        ->and(property_exists($component->instance(), 'productOptions'))->toBeFalse();

    $component->assertDontSee('Product 30');

    $component->call('searchProducts', 'Rose', 0);

    expect(productSearchOptionIds($component->instance()->productOptions(0)))->toBe([$rose->id]);

    $component->set('products.0.id', $rose->id);

    expect($component->get('productSearch'))->toBe([])
        ->and(productSearchOptionIds($component->instance()->productOptions(0, $products[30]->id)))
        ->toContain($products[30]->id);
});
