<?php

/*
 * The variation select on the deal form's product lines. Optional: a line
 * with no variation is priced from the product exactly as before.
 */

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Deals\DealCreate;
use VentureDrake\LaravelCrm\Livewire\Deals\DealEdit;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductPrice;
use VentureDrake\LaravelCrm\Models\ProductVariation;
use VentureDrake\LaravelCrm\Models\Setting;

class VariationDealCreate extends DealCreate
{
    public function render()
    {
        return '<div></div>';
    }
}

class VariationDealEdit extends DealEdit
{
    public function render()
    {
        return '<div></div>';
    }
}

beforeEach(function () {
    $this->actingAsUser();

    // DealCreate reads the install currency and the deal pipeline on mount.
    Setting::create(['name' => 'currency', 'value' => 'USD']);
    Pipeline::create(['name' => 'Deals', 'model' => Deal::class]);

    $this->widget = Product::create(['name' => 'Widget']);
    ProductPrice::create(['product_id' => $this->widget->id, 'unit_price' => 10, 'currency' => 'USD']);

    $this->large = ProductVariation::create(['product_id' => $this->widget->id, 'name' => 'Large', 'code' => 'WID-L']);
    ProductPrice::create([
        'product_id' => $this->widget->id, 'product_variation_id' => $this->large->id,
        'unit_price' => 15, 'currency' => 'USD',
    ]);

    $this->plain = Product::create(['name' => 'Plain']);
    ProductPrice::create(['product_id' => $this->plain->id, 'unit_price' => 4, 'currency' => 'USD']);
});

test('a new deal line starts with no variation', function () {
    $component = Livewire::test(VariationDealCreate::class);

    expect($component->get('products'))->toHaveCount(1)
        ->and($component->get('products.0'))->toHaveKey('product_variation_id')
        ->and($component->get('products.0.product_variation_id'))->toBeNull();
});

test('picking a variation prices the deal line from it', function () {
    $component = Livewire::test(VariationDealCreate::class)
        ->set('currency', 'USD')
        ->set('products.0.id', $this->widget->id)
        ->set('products.0.quantity', 2)
        ->set('products.0.product_variation_id', $this->large->id);

    expect($component->get('products.0.product_variation_id'))->toBe($this->large->id)
        ->and($component->get('products.0.price'))->toEqual(15)
        ->and($component->get('products.0.amount'))->toEqual(30)
        ->and($component->instance()->variationOptions($this->widget->id))->toBe([
            ['id' => $this->large->id, 'name' => 'Large (WID-L)'],
        ]);

    // Back to no variation: the product price.
    $component->set('products.0.product_variation_id', '');

    expect($component->get('products.0.product_variation_id'))->toBeNull()
        ->and($component->get('products.0.price'))->toEqual(10);
});

test('changing the deal line product clears its variation', function () {
    $component = Livewire::test(VariationDealCreate::class)
        ->set('currency', 'USD')
        ->set('products.0.id', $this->widget->id)
        ->set('products.0.product_variation_id', $this->large->id)
        ->set('products.0.id', $this->plain->id);

    expect($component->get('products.0.product_variation_id'))->toBeNull()
        ->and($component->get('products.0.price'))->toEqual(4);
});

test('a variation of another product is dropped from the deal line', function () {
    $component = Livewire::test(VariationDealCreate::class)
        ->set('currency', 'USD')
        ->set('products.0.id', $this->plain->id)
        ->set('products.0.product_variation_id', $this->large->id);

    expect($component->get('products.0.product_variation_id'))->toBeNull()
        ->and($component->get('products.0.price'))->toEqual(4)
        ->and($component->instance()->variationOptions($this->plain->id))->toBe([]);
});

test('editing a deal loads each line variation and keeps it while other fields change', function () {
    $deal = Deal::create(['title' => 'Deal', 'currency' => 'USD']);
    $deal->dealProducts()->create([
        'product_id' => $this->widget->id, 'product_variation_id' => $this->large->id,
        'quantity' => 1, 'price' => 15, 'amount' => 15,
    ]);

    // Deleted after the line was written - the line keeps it.
    $this->large->delete();

    $component = Livewire::test(VariationDealEdit::class, ['deal' => $deal]);

    expect($component->get('products.0.product_variation_id'))->toBe($this->large->id);

    $component->set('products.0.quantity', 3);

    expect($component->get('products.0.product_variation_id'))->toBe($this->large->id)
        ->and($component->get('products.0.price'))->toEqual(15)
        ->and($component->get('products.0.amount'))->toEqual(45);
});

test('a deal line whose product has since been deleted keeps a variation picked on it', function () {
    $deal = Deal::create(['title' => 'Deal', 'currency' => 'USD']);
    $deal->dealProducts()->create([
        'product_id' => $this->widget->id, 'quantity' => 2, 'price' => 10, 'amount' => 20,
    ]);

    $this->widget->delete();

    $component = Livewire::test(VariationDealEdit::class, ['deal' => $deal])
        ->set('products.0.product_variation_id', (string) $this->large->id);

    expect($component->get('products.0.product_variation_id'))->toBe($this->large->id)
        ->and($component->get('products.0.price'))->toEqual(15)
        ->and($component->get('products.0.amount'))->toEqual(30);

    // Picking a product on another row leaves it alone.
    $component->call('addProduct')->set('products.1.id', $this->plain->id);

    expect($component->get('products.0.product_variation_id'))->toBe($this->large->id)
        ->and($component->get('products.0.amount'))->toEqual(30);
});
