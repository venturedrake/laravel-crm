<?php

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Deals\DealCreate;
use VentureDrake\LaravelCrm\Livewire\Deals\DealEdit;
use VentureDrake\LaravelCrm\Livewire\Deals\DealShow;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductPrice;
use VentureDrake\LaravelCrm\Models\ProductVariation;
use VentureDrake\LaravelCrm\Models\Setting;

beforeEach(function () {
    $this->actingAsUser();
    Setting::create(['name' => 'currency', 'value' => 'USD']);
    Setting::create(['name' => 'team', 'value' => 'related']);
    app('laravel-crm.settings')->forgetCache();
    Pipeline::create(['name' => 'Deals', 'model' => Deal::class]);
    $this->person = Person::create(['first_name' => 'Product', 'last_name' => 'Tester']);
    $this->product = Product::create(['name' => 'Regression Widget']);
    ProductPrice::create(['product_id' => $this->product->id, 'unit_price' => 12, 'currency' => 'USD']);
});

test('the untouched create picker saves no product rows', function () {
    $form = Livewire::test(DealCreate::class, ['fromModelType' => 'person', 'fromModelId' => $this->person->id]);

    expect($form->get('products'))->toHaveCount(1);
    $form->assertSet('products.0.id', null)
        ->set('title', 'Productless deal')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('laravel-crm.deals.index'));

    expect(Deal::sole()->dealProducts()->withTrashed()->count())->toBe(0);
});

test('a productless deal can be reopened and saved with an empty product list', function () {
    $deal = Deal::create(['title' => 'Productless deal', 'currency' => 'USD', 'person_id' => $this->person->id]);

    Livewire::test(DealEdit::class, ['deal' => $deal])
        ->assertSet('products', [])
        ->set('title', 'Edited productless deal')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('laravel-crm.deals.show', $deal));

    expect($deal->fresh()->title)->toBe('Edited productless deal')
        ->and($deal->dealProducts()->withTrashed()->count())->toBe(0);
});

test('clearing a saved selection removes it when the deal is reopened', function () {
    $deal = Deal::create(['title' => 'Clear product', 'currency' => 'USD', 'person_id' => $this->person->id]);
    $line = $deal->dealProducts()->create(['product_id' => $this->product->id, 'price' => 12, 'quantity' => 1, 'amount' => 12]);

    Livewire::test(DealEdit::class, ['deal' => $deal])
        ->set('products.0.id', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($line->fresh()->trashed())->toBeTrue();
    Livewire::test(DealEdit::class, ['deal' => $deal->fresh()])->assertSet('products', []);
});

test('forms save a selected product alongside blank pickers', function ($editing) {
    if ($editing) {
        $deal = Deal::create(['title' => 'Mixed products', 'currency' => 'USD', 'person_id' => $this->person->id]);
        $form = Livewire::test(DealEdit::class, ['deal' => $deal])->call('addProduct');
    } else {
        $form = Livewire::test(DealCreate::class, ['fromModelType' => 'person', 'fromModelId' => $this->person->id])
            ->set('title', 'Mixed products');
    }

    $form->call('addProduct')
        ->set('products.1.id', $this->product->id)
        ->set('products.1.quantity', 1.5)
        ->call('addProduct')
        ->call('save')
        ->assertHasNoErrors();

    $line = Deal::sole()->dealProducts()->sole();
    expect($line->product_id)->toBe($this->product->id)
        ->and($line->quantity)->toBe(1.5)
        ->and((int) $line->getRawOriginal('price'))->toBe(1200)
        ->and((int) $line->getRawOriginal('amount'))->toBe(1800);
})->with(['create' => false, 'edit' => true]);

test('the real show view counts exactly the displayed product rows', function ($blankCount, $validCount, $deleted) {
    $deal = Deal::create(['title' => 'Displayed products', 'currency' => 'USD']);
    for ($i = 0; $i < $blankCount; $i++) {
        $deal->dealProducts()->create(['product_id' => null, 'quantity' => 1]);
    }
    $variation = ProductVariation::create(['product_id' => $this->product->id, 'name' => 'Large']);
    for ($i = 0; $i < $validCount; $i++) {
        $deal->dealProducts()->create([
            'product_id' => $this->product->id, 'product_variation_id' => $variation->id,
            'price' => 12, 'quantity' => 1.5, 'amount' => 18, 'currency' => 'USD',
        ]);
    }
    if ($deleted) {
        $this->product->delete();
        $variation->delete();
    }

    $view = Livewire::test(DealShow::class, ['deal' => $deal])
        ->assertSee('Products ('.$validCount.')')
        ->assertViewHas('dealProducts', function ($lines) use ($validCount) {
            return $lines->count() === $validCount && $lines->every(fn ($line) => $line->relationLoaded('product') && $line->relationLoaded('productVariation'));
        });

    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$view->html());
    $xpath = new DOMXPath($document);
    $rows = $xpath->query('//table[thead/tr/th[normalize-space()="Quantity"]]/tbody/tr');
    expect($rows->length)->toBe($validCount);
    foreach ($rows as $row) {
        expect($row->textContent)->toContain('Regression Widget — Large');
    }
    expect($deal->dealProducts()->count())->toBe($blankCount + $validCount);
})->with([
    'empty' => [0, 0, false],
    'historical blanks' => [2, 0, false],
    'mixed' => [2, 2, false],
    'deleted references' => [1, 1, true],
]);
