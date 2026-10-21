<?php

use Illuminate\Support\Facades\Facade;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Products\ProductCreate;
use VentureDrake\LaravelCrm\Livewire\Products\ProductEdit;
use VentureDrake\LaravelCrm\Livewire\Products\ProductIndex;
use VentureDrake\LaravelCrm\Livewire\Products\ProductShow;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductPrice;
use VentureDrake\LaravelCrm\Models\ProductVariation;

/*
 * The product form edits a product's variations as an in-memory list of rows
 * which ProductService::syncVariations() persists on save. These tests drive
 * the real ProductCreate / ProductEdit components (and their blades) end to end.
 */

beforeEach(function () {
    app('laravel-crm.settings')->set('currency', 'USD');

    $this->user = $this->actingAsUser(['crm_access' => 1]);
});

function variationRow(array $overrides = []): array
{
    return array_merge([
        'id' => null,
        'name' => '',
        'code' => null,
        'barcode' => null,
        'unit_price' => null,
        'active' => true,
    ], $overrides);
}

/**
 * A product priced at $10.00 with Small ($12.50) and Large (no own price).
 */
function productWithVariations(): Product
{
    $product = Product::create(['name' => 'Widget', 'user_owner_id' => auth()->id()]);
    $product->productPrices()->create(['unit_price' => 10, 'currency' => 'USD']);

    $small = $product->productVariations()->create(['name' => 'Small', 'code' => 'W-S', 'order' => 1, 'active' => true]);
    $small->productPrices()->create(['product_id' => $product->id, 'unit_price' => '12.50', 'currency' => 'USD']);

    $product->productVariations()->create(['name' => 'Large', 'code' => 'W-L', 'order' => 2, 'active' => false]);

    return $product;
}

test('the create form starts with no variation rows and can add one', function () {
    Livewire::test(ProductCreate::class)
        ->assertSet('variations', [])
        ->assertSee(ucfirst(__('laravel-crm::lang.add_variation')))
        ->call('addVariation')
        ->assertSet('variations', [variationRow()]);
});

test('creating a product persists its variations, prices and audit columns', function () {
    Livewire::test(ProductCreate::class)
        ->set('name', 'Widget')
        ->set('unit_price', '10.00')
        ->call('addVariation')
        ->call('addVariation')
        ->set('variations.0.name', 'Small')
        ->set('variations.0.code', 'W-S')
        ->set('variations.0.barcode', '111')
        ->set('variations.0.unit_price', '1,234.50')
        ->set('variations.1.name', 'Large')
        ->set('variations.1.code', 'W-L')
        ->set('variations.1.active', false)
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::where('name', 'Widget')->firstOrFail();
    [$small, $large] = $product->productVariations()->get()->all();

    expect($small->only(['name', 'code', 'barcode', 'order', 'active']))
        ->toBe(['name' => 'Small', 'code' => 'W-S', 'barcode' => '111', 'order' => 1, 'active' => true]);
    expect($large->only(['name', 'code', 'order', 'active']))
        ->toBe(['name' => 'Large', 'code' => 'W-L', 'order' => 2, 'active' => false]);

    $smallPrice = $small->productPrices()->sole();
    expect($smallPrice->product_id)->toBe($product->id)
        ->and($smallPrice->product_variation_id)->toBe($small->id)
        ->and($smallPrice->currency)->toBe('USD')
        ->and((int) $smallPrice->getRawOriginal('unit_price'))->toBe(123450);
    expect($large->productPrices()->count())->toBe(0);

    // The product's own price is product-level, and is still its default price.
    $productPrice = ProductPrice::where('product_id', $product->id)->whereNull('product_variation_id')->sole();
    expect($product->getDefaultPrice()->id)->toBe($productPrice->id)
        ->and((int) $productPrice->getRawOriginal('unit_price'))->toBe(1000);

    foreach ([$small, $large] as $variation) {
        expect($variation->external_id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/')
            ->and($variation->user_created_id)->toBe($this->user->id);
    }
});

test('the drawer layout create form also saves variations', function () {
    Livewire::test(ProductCreate::class, ['layout' => 'drawer'])
        ->set('name', 'Gadget')
        ->call('addVariation')
        ->set('variations.0.name', 'Blue')
        ->call('createProduct')
        ->assertHasNoErrors()
        ->assertDispatched('product-created');

    expect(Product::where('name', 'Gadget')->sole()->productVariations()->pluck('name')->all())->toBe(['Blue']);
});

test('the edit form loads variation rows with their prices', function () {
    $product = productWithVariations();
    [$small, $large] = $product->productVariations()->get()->all();

    Livewire::test(ProductEdit::class, ['product' => $product])
        ->assertSet('currency', 'USD')
        ->assertSet('variations', [
            ['id' => $small->id, 'name' => 'Small', 'code' => 'W-S', 'barcode' => null, 'unit_price' => 12.5, 'active' => true],
            ['id' => $large->id, 'name' => 'Large', 'code' => 'W-L', 'barcode' => null, 'unit_price' => null, 'active' => false],
        ])
        // wire:model values are filled client-side, so check the bound inputs.
        ->assertSeeHtml('wire:model="variations.1.name"')
        ->assertSeeHtml('wire:model="variations.1.active"')
        // No "up" on the first row, no "down" on the last.
        ->assertDontSeeHtml('wire:click="moveVariationUp(0)"')
        ->assertSeeHtml('wire:click="moveVariationDown(0)"')
        ->assertSeeHtml('wire:click="moveVariationUp(1)"')
        ->assertDontSeeHtml('wire:click="moveVariationDown(1)"');
});

test('editing renames, reorders, removes, clears a price and adds variations', function () {
    $product = productWithVariations();
    $medium = $product->productVariations()->create(['name' => 'Medium', 'code' => 'W-M', 'order' => 3]);
    $medium->productPrices()->create(['product_id' => $product->id, 'unit_price' => 11, 'currency' => 'USD']);
    [$small, $large] = $product->productVariations()->get()->all();

    Livewire::test(ProductEdit::class, ['product' => $product])
        // Rows: Small, Large, Medium.
        ->set('variations.0.name', 'Petite')
        ->call('moveVariationDown', 0)
        // Rows: Large, Petite, Medium.
        ->call('removeVariation', 0)
        // Rows: Petite, Medium.
        ->set('variations.1.unit_price', '')
        ->call('addVariation')
        ->set('variations.2.name', 'Huge')
        ->set('variations.2.code', 'W-H')
        ->set('variations.2.unit_price', '20')
        ->call('save')
        ->assertHasNoErrors();

    $rows = $product->productVariations()->get();

    expect($rows->pluck('name')->all())->toBe(['Petite', 'Medium', 'Huge'])
        ->and($rows->pluck('order')->all())->toBe([1, 2, 3])
        ->and($rows[0]->id)->toBe($small->id)
        ->and($rows[1]->id)->toBe($medium->id);

    // Removed: soft-deleted, still resolvable for lines that reference it.
    expect(ProductVariation::find($large->id))->toBeNull();
    $trashed = ProductVariation::withTrashed()->find($large->id);
    expect($trashed)->not->toBeNull()
        ->and($trashed->deleted_at)->not->toBeNull();

    // Cleared price: Medium now falls back to the product price.
    expect($medium->fresh()->productPrices()->count())->toBe(0)
        ->and($product->unitPriceFor($medium->fresh(), 'USD'))->toBe(1000);

    // Untouched price survives the rename and move.
    expect((int) $small->fresh()->priceFor('USD')->getRawOriginal('unit_price'))->toBe(1250);

    $huge = $rows[2];
    expect($huge->code)->toBe('W-H')
        ->and($huge->user_created_id)->toBe($this->user->id)
        ->and((int) $huge->priceFor('USD')->getRawOriginal('unit_price'))->toBe(2000);
});

test('editing a variation price with another currency selected keeps it in the product price currency', function () {
    $product = productWithVariations();
    $small = $product->productVariations()->firstWhere('name', 'Small');

    // The product price ignores the currency select on edit, so the variation
    // prices follow it rather than starting a second, hidden EUR row.
    Livewire::test(ProductEdit::class, ['product' => $product])
        ->set('currency', 'EUR')
        ->set('variations.0.unit_price', '15')
        ->call('save')
        ->assertHasNoErrors();

    expect($small->productPrices()->pluck('currency')->all())->toBe(['USD'])
        ->and((int) $small->priceFor('USD')->getRawOriginal('unit_price'))->toBe(1500);

    Livewire::test(ProductEdit::class, ['product' => $product->fresh()])
        ->assertSet('variations.0.unit_price', 15);
});

test('editing a product priced only in another currency keeps its prices', function () {
    $product = Product::create(['name' => 'Widget', 'user_owner_id' => auth()->id()]);
    $product->productPrices()->create(['unit_price' => 10, 'currency' => 'EUR']);
    $small = $product->productVariations()->create(['name' => 'Small', 'order' => 1, 'active' => true]);
    $small->productPrices()->create(['product_id' => $product->id, 'unit_price' => 12, 'currency' => 'EUR']);

    // There is no USD row, so the form loads the EUR prices rather than
    // blanks - which saving would then have cleared.
    Livewire::test(ProductEdit::class, ['product' => $product])
        ->assertSet('currency', 'EUR')
        ->assertSet('unit_price', 10)
        ->assertSet('variations.0.unit_price', 12)
        ->call('save')
        ->assertHasNoErrors();

    expect($product->productPrices()->get()->map->only(['currency', 'unit_price'])->all())
        ->toBe([['currency' => 'EUR', 'unit_price' => 1000]])
        ->and($small->productPrices()->get()->map->only(['currency', 'unit_price'])->all())
        ->toBe([['currency' => 'EUR', 'unit_price' => 1200]]);
});

test('row reordering and removal ignore out of range indexes', function () {
    $component = Livewire::test(ProductCreate::class)
        ->call('addVariation')
        ->set('variations.0.name', 'Only')
        ->call('moveVariationUp', 0)
        ->call('moveVariationDown', 0)
        ->call('removeVariation', 5);

    expect($component->get('variations'))->toBe([variationRow(['name' => 'Only'])]);
});

test('a variation needs a name', function () {
    Livewire::test(ProductCreate::class)
        ->set('name', 'Widget')
        ->call('addVariation')
        ->call('save')
        ->assertHasErrors(['variations.0.name' => 'required']);

    expect(Product::count())->toBe(0);
});

test('a variation price must be a non-negative amount', function () {
    Livewire::test(ProductCreate::class)
        ->set('name', 'Widget')
        ->call('addVariation')
        ->call('addVariation')
        ->set('variations.0.name', 'A')
        ->set('variations.0.unit_price', 'abc')
        ->set('variations.1.name', 'B')
        ->set('variations.1.unit_price', '-5')
        ->call('save')
        ->assertHasErrors(['variations.0.unit_price', 'variations.1.unit_price']);
});

test('the show page lists variations with their own or the fallback price', function () {
    $product = productWithVariations();

    Livewire::test(ProductShow::class, ['product' => $product])
        ->assertSee('Small')
        ->assertSee('W-S')
        ->assertSee('Large')
        ->assertSee(money(1250, 'USD'))
        ->assertDontSee(ucfirst(__('laravel-crm::lang.no_variations')));
});

test('the show page prices variations of a product priced only in another currency', function () {
    $product = Product::create(['name' => 'Widget', 'user_owner_id' => auth()->id()]);
    $product->productPrices()->create(['unit_price' => 10, 'currency' => 'EUR']);
    $small = $product->productVariations()->create(['name' => 'Small', 'order' => 1, 'active' => true]);
    $small->productPrices()->create(['product_id' => $product->id, 'unit_price' => 12, 'currency' => 'EUR']);
    $product->productVariations()->create(['name' => 'Large', 'order' => 2, 'active' => true]);

    // Small at its own price, Large at the product's fallback price.
    Livewire::test(ProductShow::class, ['product' => $product])
        ->assertSee(money(1200, 'EUR'))
        ->assertSee(money(1000, 'EUR'));
});

test('the show page says when a product has no variations', function () {
    $product = Product::create(['name' => 'Plain']);

    Livewire::test(ProductShow::class, ['product' => $product])
        ->assertSee(ucfirst(__('laravel-crm::lang.no_variations')));
});

test('searching the product index by a variation code finds the parent product', function () {
    productWithVariations();
    Product::create(['name' => 'Gizmo']);

    $component = Livewire::test(ProductIndex::class)->set('search', 'W-L');

    expect($component->instance()->products()->pluck('name')->all())->toBe(['Widget']);

    $component->set('search', 'Giz');

    expect($component->instance()->products()->pluck('name')->all())->toBe(['Gizmo']);
});

/**
 * A connected Xero that records the Items it is sent.
 */
function connectedXeroForProducts(): object
{
    $xero = new class
    {
        public array $posted = [];

        public function isConnected(): bool
        {
            return true;
        }

        public function post(string $endpoint, array $payload): array
        {
            $this->posted[] = $payload;

            return ['body' => ['Items' => [[
                'ItemID' => 'xero-item-1', 'Code' => $payload['Code'], 'Name' => $payload['Name'],
                'IsTrackedAsInventory' => false, 'IsSold' => true, 'IsPurchased' => true,
                'SalesDetails' => $payload['SalesDetails'],
            ]]]];
        }

        public function __call($name, $args)
        {
            return null;
        }
    };

    app()->instance('xero', $xero);
    Facade::clearResolvedInstance('xero');

    return $xero;
}

test('with Xero connected, a product priced only in another currency creates and saves', function () {
    $xero = connectedXeroForProducts();

    Livewire::test(ProductCreate::class)
        ->set('name', 'Widget')
        ->set('currency', 'EUR')
        ->set('unit_price', '10.00')
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::where('name', 'Widget')->sole();

    Livewire::test(ProductEdit::class, ['product' => $product])
        ->set('unit_price', '20.00')
        ->call('save')
        ->assertHasNoErrors();

    // Xero gets the price the product actually has, not a missing USD one.
    expect(array_column(array_column($xero->posted, 'SalesDetails'), 'UnitPrice'))->toBe([10, 20]);
});
