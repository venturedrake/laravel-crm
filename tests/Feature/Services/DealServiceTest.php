<?php

use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductVariation;
use VentureDrake\LaravelCrm\Services\DealService;

beforeEach(function () {
    $this->actingAsUser();
    $this->service = app(DealService::class);
    $this->product = Product::create(['name' => 'Widget']);
    $this->variation = ProductVariation::create(['product_id' => $this->product->id, 'name' => 'Large']);
});

function dealProductRequest(array $attributes = []): Request
{
    return new Request($attributes + ['title' => 'Product regression', 'currency' => 'USD']);
}

function validDealProductRow($product, $variation, $quantity = 2): array
{
    return ['id' => $product->id, 'product_variation_id' => $variation->id,
        'price' => 12.34, 'quantity' => $quantity, 'amount' => 12.34 * $quantity];
}

test('deal saves ignore blank product rows before reading other fields', function ($operation, $row) {
    $request = dealProductRequest(['products' => [$row]]);
    $deal = $operation === 'create'
        ? $this->service->create($request)
        : $this->service->update($request, Deal::create(['title' => 'Existing', 'currency' => 'USD']));

    expect($deal->dealProducts()->withTrashed()->count())->toBe(0);
})->with(['create', 'update'])->with([
    'null id' => [['id' => null]],
    'empty id' => [['id' => '']],
    'missing id' => [[]],
]);

test('deal saves persist valid lines alongside blank rows', function ($operation, $quantity) {
    $request = dealProductRequest(['products' => [
        ['id' => null], validDealProductRow($this->product, $this->variation, $quantity), ['id' => ''], [],
    ]]);
    $deal = $operation === 'create'
        ? $this->service->create($request)
        : $this->service->update($request, Deal::create(['title' => 'Existing', 'currency' => 'USD']));
    $line = $deal->dealProducts()->sole();

    expect($line->product_id)->toBe($this->product->id)
        ->and($line->product_variation_id)->toBe($this->variation->id)
        ->and($line->quantity)->toBe((float) $quantity)
        ->and((int) $line->getRawOriginal('price'))->toBe(1234)
        ->and((int) $line->getRawOriginal('amount'))->toBe((int) round(1234 * $quantity));
})->with(['create', 'update'])->with([2, 1.5]);

test('updating a saved line preserves deleted product and variation references and money', function ($quantity) {
    $row = validDealProductRow($this->product, $this->variation);
    $deal = $this->service->create(dealProductRequest(['products' => [$row]]));
    $line = $deal->dealProducts()->sole();
    $this->product->delete();
    $this->variation->delete();

    $this->service->update(dealProductRequest(['products' => [
        ['id' => null],
        ['deal_product_id' => $line->id] + validDealProductRow($this->product, $this->variation, $quantity),
    ]]), $deal);

    $line->refresh();
    expect($deal->dealProducts()->count())->toBe(1)
        ->and($line->product_id)->toBe($this->product->id)
        ->and($line->product_variation_id)->toBe($this->variation->id)
        ->and($line->quantity)->toBe((float) $quantity)
        ->and((int) $line->getRawOriginal('price'))->toBe(1234)
        ->and((int) $line->getRawOriginal('amount'))->toBe((int) round(1234 * $quantity));
})->with([3, 1.5]);

test('clearing a saved product soft deletes only that deal line', function ($row) {
    $request = dealProductRequest(['products' => [validDealProductRow($this->product, $this->variation)]]);
    $deal = $this->service->create($request);
    $otherDeal = $this->service->create($request);
    $line = $deal->dealProducts()->sole();
    $otherLine = $otherDeal->dealProducts()->sole();

    $this->service->update(dealProductRequest(['products' => [
        ['deal_product_id' => $line->id] + $row,
        ['deal_product_id' => $otherLine->id] + $row,
    ]]), $deal);

    expect($deal->dealProducts()->count())->toBe(0)
        ->and($line->fresh()->trashed())->toBeTrue()
        ->and($otherLine->fresh()->trashed())->toBeFalse()
        ->and($otherDeal->dealProducts()->count())->toBe(1);
})->with([
    'null id' => [['id' => null]],
    'empty id' => [['id' => '']],
    'missing id' => [[]],
]);

test('omitting products or an existing line leaves it intact', function ($attributes) {
    $deal = $this->service->create(dealProductRequest(['products' => [validDealProductRow($this->product, $this->variation)]]));
    $line = $deal->dealProducts()->sole();

    $this->service->update(dealProductRequest($attributes), $deal);

    expect($deal->dealProducts()->sole()->id)->toBe($line->id)
        ->and($line->fresh()->trashed())->toBeFalse();
})->with(['omitted' => [[]], 'empty list' => [['products' => []]], 'blank new row' => [['products' => [['id' => null]]]]]);
