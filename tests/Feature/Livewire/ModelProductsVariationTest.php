<?php

/*
 * Picking a product variation on a line in the shared product form
 * (quote, order, invoice, purchase order) - and seeding a new quote's lines
 * from a deal.
 *
 * The variation is optional: a line with none is priced from the product,
 * exactly as before variations existed, and so is a line whose variation has
 * no price in the document currency.
 */

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\ModelProducts;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductPrice;
use VentureDrake\LaravelCrm\Models\ProductVariation;
use VentureDrake\LaravelCrm\Models\TaxRate;

function variationTestProduct(string $name = 'Widget', $price = 10, array $attributes = []): Product
{
    $product = Product::create(array_merge(['name' => $name], $attributes));

    ProductPrice::create(array_merge([
        'product_id' => $product->id,
        'unit_price' => $price,
        'currency' => 'USD',
    ], array_intersect_key($attributes, ['team_id' => true])));

    return $product;
}

function variationTestVariation(Product $product, string $name, array $prices = [], array $attributes = []): ProductVariation
{
    $variation = ProductVariation::create(array_merge([
        'product_id' => $product->id,
        'name' => $name,
    ], $attributes));

    foreach ($prices as $currency => $price) {
        ProductPrice::create([
            'product_id' => $product->id,
            'product_variation_id' => $variation->id,
            'unit_price' => $price,
            'currency' => $currency,
            'team_id' => $attributes['team_id'] ?? null,
        ]);
    }

    return $variation;
}

test('picking a variation sets it on the line and prices the line from it in the document currency', function () {
    $this->actingAsUser();

    $product = variationTestProduct('Widget', 10);
    $large = variationTestVariation($product, 'Large', ['USD' => 15, 'AUD' => 20]);

    $component = Livewire::test(ModelProducts::class, ['currency' => 'AUD'])
        ->set('products.0.id', $product->id)
        ->set('products.0.product_variation_id', $large->id);

    expect($component->get('products.0.product_variation_id'))->toBe($large->id)
        ->and($component->get('products.0.unit_price'))->toEqual(20)
        ->and($component->get('products.0.amount'))->toEqual(20.0);

    $component->assertSet('sub_total', 20.0);
});

test('a variation with no price in the document currency falls back to the product price', function () {
    $this->actingAsUser();

    $product = variationTestProduct('Widget', 10);
    $large = variationTestVariation($product, 'Large', ['EUR' => 15]);

    $component = Livewire::test(ModelProducts::class, ['currency' => 'USD'])
        ->set('products.0.id', $product->id)
        ->set('products.0.product_variation_id', $large->id);

    expect($component->get('products.0.product_variation_id'))->toBe($large->id)
        ->and($component->get('products.0.unit_price'))->toEqual(10);
});

test('choosing no variation goes back to the product price', function () {
    $this->actingAsUser();

    $product = variationTestProduct('Widget', 10);
    $large = variationTestVariation($product, 'Large', ['USD' => 15]);

    $component = Livewire::test(ModelProducts::class, ['currency' => 'USD'])
        ->set('products.0.id', $product->id)
        ->set('products.0.product_variation_id', $large->id)
        ->set('products.0.product_variation_id', '');

    expect($component->get('products.0.product_variation_id'))->toBeNull()
        ->and($component->get('products.0.unit_price'))->toEqual(10);
});

test('changing the product clears the variation and reprices the line', function () {
    $this->actingAsUser();

    $widget = variationTestProduct('Widget', 10);
    $large = variationTestVariation($widget, 'Large', ['USD' => 15]);
    $gadget = variationTestProduct('Gadget', 7);

    $component = Livewire::test(ModelProducts::class, ['currency' => 'USD'])
        ->set('products.0.id', $widget->id)
        ->set('products.0.product_variation_id', $large->id)
        ->set('products.0.id', $gadget->id);

    expect($component->get('products.0.product_variation_id'))->toBeNull()
        ->and($component->get('products.0.unit_price'))->toEqual(7);
});

test('a variation of another product is ignored', function () {
    $this->actingAsUser();

    $widget = variationTestProduct('Widget', 10);
    $gadget = variationTestProduct('Gadget', 7);
    $gadgetLarge = variationTestVariation($gadget, 'Large', ['USD' => 99]);

    $component = Livewire::test(ModelProducts::class, ['currency' => 'USD'])
        ->set('products.0.id', $widget->id)
        ->set('products.0.product_variation_id', $gadgetLarge->id);

    expect($component->get('products.0.product_variation_id'))->toBeNull()
        ->and($component->get('products.0.unit_price'))->toEqual(10);
});

test('a variation of another team is ignored', function () {
    config()->set('laravel-crm.teams', true);

    try {
        $user = $this->actingAsUser(['current_team_id' => 1, 'team_ids' => json_encode([1])]);

        // The stub's currentTeam is a bare object; the nested product-create
        // drawer lists the team's users while rendering.
        $user->setRelation('currentTeam', new class
        {
            public int $id = 1;

            public function allUsers()
            {
                return collect();
            }
        });

        $widget = variationTestProduct('Widget', 10, ['team_id' => 1]);

        // Same product id, other team - as a crafted request would send it.
        $foreign = variationTestVariation($widget, 'Foreign', ['USD' => 99], ['team_id' => 2]);

        $component = Livewire::test(ModelProducts::class, ['currency' => 'USD'])
            ->set('products.0.id', $widget->id)
            ->set('products.0.product_variation_id', $foreign->id);

        expect($component->get('products.0.product_variation_id'))->toBeNull()
            ->and($component->get('products.0.unit_price'))->toEqual(10)
            ->and($component->instance()->variationOptions($widget->id))->toBe([]);
    } finally {
        config()->set('laravel-crm.teams', false);
    }
});

test('a product with no variations has no options and is priced exactly as before', function () {
    $this->actingAsUser();

    $product = variationTestProduct('Plain', 12.5);

    $component = Livewire::test(ModelProducts::class, ['currency' => 'USD'])
        ->set('products.0.id', $product->id);

    expect($component->instance()->variationOptions($product->id))->toBe([])
        ->and($component->instance()->variationOptions(null))->toBe([])
        ->and($component->instance()->variationOptions('typed name'))->toBe([])
        ->and($component->get('products.0.product_variation_id'))->toBeNull()
        ->and($component->get('products.0.unit_price'))->toEqual(12.5)
        ->and($component->get('products.0.amount'))->toEqual(12.5);

    expect($component->html())->not->toContain('product_variation_id');
});

test('variation options list the active variations in order, with their code', function () {
    $this->actingAsUser();

    $product = variationTestProduct('Widget', 10);
    $large = variationTestVariation($product, 'Large', [], ['code' => 'WID-L', 'order' => 2]);
    $small = variationTestVariation($product, 'Small', [], ['order' => 1]);
    variationTestVariation($product, 'Retired', [], ['order' => 3, 'active' => false]);

    $component = Livewire::test(ModelProducts::class, ['currency' => 'USD'])
        ->set('products.0.id', $product->id);

    expect($component->instance()->variationOptions($product->id))->toBe([
        ['id' => $small->id, 'name' => 'Small'],
        ['id' => $large->id, 'name' => 'Large (WID-L)'],
    ]);

    expect($component->html())->toContain('wire:model.live="products.0.product_variation_id"');
});

test('a line already on a deleted variation keeps it in its options', function () {
    $this->actingAsUser();

    $product = variationTestProduct('Widget', 10);
    $large = variationTestVariation($product, 'Large', ['USD' => 15]);

    $deal = Deal::create(['title' => 'D', 'currency' => 'USD']);
    $deal->dealProducts()->create([
        'product_id' => $product->id, 'product_variation_id' => $large->id,
        'quantity' => 1, 'price' => 15, 'amount' => 15,
    ]);

    $large->delete();

    $component = Livewire::test(ModelProducts::class, ['model' => $deal, 'currency' => 'USD']);

    expect($component->instance()->variationOptions($product->id))->toBe([])
        ->and($component->instance()->variationOptions($product->id, $large->id))->toBe([
            ['id' => $large->id, 'name' => 'Large'],
        ]);

    expect($component->html())->toContain('wire:model.live="products.0.product_variation_id"');
});

test('picking a variation on a line whose product has since been deleted keeps it', function () {
    $this->actingAsUser();

    $product = variationTestProduct('Widget', 10);
    $small = variationTestVariation($product, 'Small', ['USD' => 12]);
    $large = variationTestVariation($product, 'Large', ['USD' => 15]);

    $deal = Deal::create(['title' => 'D', 'currency' => 'USD']);
    $deal->dealProducts()->create([
        'product_id' => $product->id, 'product_variation_id' => $small->id,
        'quantity' => 1, 'price' => 12, 'amount' => 12,
    ]);

    $product->delete();

    $component = Livewire::test(ModelProducts::class, ['model' => $deal, 'currency' => 'USD'])
        ->set('products.0.product_variation_id', $large->id);

    expect($component->get('products.0.product_variation_id'))->toBe($large->id)
        ->and($component->get('products.0.unit_price'))->toEqual(15);
});

test('mounting with a deal builds editable lines from its products', function () {
    $this->actingAsUser();

    $taxRate = TaxRate::create(['name' => 'GST', 'rate' => 10]);
    $product = variationTestProduct('Widget', 10, ['tax_rate_id' => $taxRate->id]);
    $large = variationTestVariation($product, 'Large', ['USD' => 15]);
    $plain = variationTestProduct('Plain', 4);

    $deal = Deal::create(['title' => 'D', 'currency' => 'USD']);
    $largeLine = $deal->dealProducts()->create([
        'product_id' => $product->id, 'product_variation_id' => $large->id,
        'quantity' => 2, 'price' => 15, 'amount' => 30,
    ]);
    $plainLine = $deal->dealProducts()->create([
        'product_id' => $plain->id, 'quantity' => 1, 'price' => 4, 'amount' => 4,
    ]);
    // A free-text deal line with no product is not a product line.
    $deal->dealProducts()->create(['quantity' => 1, 'price' => 1, 'amount' => 1]);

    $component = Livewire::test(ModelProducts::class, ['model' => $deal, 'currency' => 'USD']);

    $products = $component->get('products');

    expect($products)->toHaveCount(2)
        ->and($products[0]['deal_product_id'])->toBe($largeLine->id)
        ->and($products[0]['id'])->toBe($product->id)
        ->and($products[0]['product_variation_id'])->toBe($large->id)
        ->and($products[0]['name'])->toBe('Widget — Large')
        ->and($products[0]['unit_price'])->toEqual(15)
        ->and($products[0]['tax_rate'])->toEqual(10.0)
        ->and($products[0]['tax_amount'])->toEqual(3.0)
        ->and($products[0]['amount'])->toEqual(30.0)
        ->and($products[1]['deal_product_id'])->toBe($plainLine->id)
        ->and($products[1]['product_variation_id'])->toBeNull();

    $component->assertSet('sub_total', 34.0);

    // Editable: the product select and the add button are rendered, not the
    // read-only name.
    $html = $component->html();
    expect($html)->toContain('wire:model.live="products.0.id"')
        ->and($html)->toContain('wire:click="add"')
        ->and($html)->not->toContain('wire:model="products.0.name"');

    $component->set('products.1.quantity', 3);
    expect($component->get('products.1.amount'))->toEqual(12.0);
});

test('mounting with a deal that has no product lines gives a blank row', function () {
    $this->actingAsUser();

    $deal = Deal::create(['title' => 'D', 'currency' => 'USD']);

    $products = Livewire::test(ModelProducts::class, ['model' => $deal])->get('products');

    expect($products)->toHaveCount(1)
        ->and($products[0]['id'])->toBeNull()
        ->and($products[0]['product_variation_id'])->toBeNull();
});

test('an unrelated model falls back to a blank row', function () {
    $this->actingAsUser();

    $person = Person::create(['first_name' => 'Jane']);

    $products = Livewire::test(ModelProducts::class, ['model' => $person])->get('products');

    expect($products)->toHaveCount(1)
        ->and($products[0]['id'])->toBeNull();
});
