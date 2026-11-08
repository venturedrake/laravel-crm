<?php

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Settings\TaxRates\TaxRateIndex;
use VentureDrake\LaravelCrm\Livewire\Settings\TaxRates\TaxRateShow;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\TaxRate;

/*
 * #103: a tax rate that products still use cannot be deleted.
 *
 * Once a rate is soft-deleted, `$product->taxRate` resolves to null (a blank
 * rate on the product page) while new lines keep charging the product's stored
 * `tax_rate` copy. All three delete paths (index, show, legacy controller)
 * refuse the delete while any live product points at the rate.
 */

beforeEach(function () {
    $this->actingAsUser(['crm_access' => 1]);
});

function taxRateWithProduct(): TaxRate
{
    $taxRate = TaxRate::create(['name' => 'Test 15', 'rate' => 15]);

    Product::create(['name' => 'Widget', 'tax_rate_id' => $taxRate->id, 'tax_rate' => 15]);

    return $taxRate;
}

test('index refuses to delete a tax rate products use', function () {
    $taxRate = taxRateWithProduct();

    Livewire::test(TaxRateIndex::class)->call('delete', $taxRate->id);

    $this->assertNotSoftDeleted('crm_tax_rates', ['id' => $taxRate->id]);
    expect(Product::first()->taxRate?->id)->toBe($taxRate->id);
});

test('index deletes a tax rate no product uses', function () {
    $taxRate = TaxRate::create(['name' => 'Unused', 'rate' => 5]);

    Livewire::test(TaxRateIndex::class)->call('delete', $taxRate->id);

    $this->assertSoftDeleted('crm_tax_rates', ['id' => $taxRate->id]);
});

test('show refuses to delete a tax rate products use and does not redirect', function () {
    $taxRate = taxRateWithProduct();

    Livewire::test(TaxRateShow::class, ['taxRate' => $taxRate])
        ->call('delete', $taxRate->id)
        ->assertNoRedirect();

    $this->assertNotSoftDeleted('crm_tax_rates', ['id' => $taxRate->id]);
});

test('show deletes a tax rate no product uses and redirects to the index', function () {
    $taxRate = TaxRate::create(['name' => 'Unused', 'rate' => 5]);

    Livewire::test(TaxRateShow::class, ['taxRate' => $taxRate])
        ->call('delete', $taxRate->id)
        ->assertRedirect(route('laravel-crm.tax-rates.index'));

    $this->assertSoftDeleted('crm_tax_rates', ['id' => $taxRate->id]);
});

test('legacy destroy route refuses to delete a tax rate products use', function () {
    $taxRate = taxRateWithProduct();

    $this->from(route('laravel-crm.tax-rates.show', $taxRate))
        ->delete(route('laravel-crm.tax-rates.destroy', $taxRate))
        ->assertRedirect(route('laravel-crm.tax-rates.show', $taxRate));

    $this->assertNotSoftDeleted('crm_tax_rates', ['id' => $taxRate->id]);
});

test('a soft-deleted product does not block deleting its tax rate', function () {
    $taxRate = taxRateWithProduct();
    Product::first()->delete();

    Livewire::test(TaxRateIndex::class)->call('delete', $taxRate->id);

    $this->assertSoftDeleted('crm_tax_rates', ['id' => $taxRate->id]);
});
