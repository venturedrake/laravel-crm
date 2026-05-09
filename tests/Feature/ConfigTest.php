<?php

test('default db table prefix is crm', function () {
    expect(config('laravel-crm.db_table_prefix'))->toBe('crm_');
});

test('default route prefix is crm', function () {
    expect(config('laravel-crm.route_prefix'))->toBe('crm');
});

test('default modules array includes all features', function () {
    $modules = config('laravel-crm.modules');

    expect($modules)->toBeArray()
        ->toContain('leads')
        ->toContain('deals')
        ->toContain('quotes')
        ->toContain('orders')
        ->toContain('invoices')
        ->toContain('deliveries')
        ->toContain('purchase-orders')
        ->toContain('teams');
});

test('model with global includes settings', function () {
    expect(config('laravel-crm.model_with_global'))->toContain('settings');
});

test('user interface defaults to true', function () {
    expect((bool) config('laravel-crm.user_interface'))->toBeTrue();
});

test('encrypt db fields defaults to false', function () {
    expect((bool) config('laravel-crm.encrypt_db_fields'))->toBeFalse();
});

test('teams defaults to false', function () {
    expect((bool) config('laravel-crm.teams'))->toBeFalse();
});
