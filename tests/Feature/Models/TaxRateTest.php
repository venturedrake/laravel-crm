<?php

use Illuminate\Database\Eloquent\Relations\HasMany;
use VentureDrake\LaravelCrm\Models\TaxRate;

test('tax rate uses prefixed table name', function () {
    expect((new TaxRate)->getTable())->toBe('crm_tax_rates');
});

test('tax rate persists default flag and rate', function () {
    $tax = TaxRate::create(['name' => 'GST', 'rate' => 10, 'default' => true]);

    expect((bool) $tax->fresh()->default)->toBeTrue();
    expect($tax->fresh()->rate)->toEqual(10);
});

test('tax rate relationship is defined', function () {
    expect((new TaxRate)->products())->toBeInstanceOf(HasMany::class);
});

test('tax rate uses soft deletes', function () {
    $tax = TaxRate::create(['name' => 'Bin', 'rate' => 0]);
    $tax->delete();
    $this->assertSoftDeleted('crm_tax_rates', ['id' => $tax->id]);
});
