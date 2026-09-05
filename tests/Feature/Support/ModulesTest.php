<?php

use VentureDrake\LaravelCrm\Support\Modules;

/*
 * Modules::enabled() has to agree with the `@has*enabled` Blade directives
 * exactly — the settings page decides which tabs exist from this class while
 * the fields inside those tabs still carry their own directives, so any
 * disagreement shows up as a tab with nothing in it (or a hidden field the
 * admin can't reach). The `[]` cases are the ones a plausible rewrite breaks;
 * tests/Feature/BladeDirectivesTest.php pins the directive side of the pair.
 */

test('every module is enabled when the config is null', function () {
    config()->set('laravel-crm.modules', null);

    expect(Modules::enabled('leads'))->toBeTrue();
    expect(Modules::enabled('quotes'))->toBeTrue();
    expect(Modules::enabled('purchase-orders'))->toBeTrue();
});

test('every module is enabled when the config is false', function () {
    config()->set('laravel-crm.modules', false);

    expect(Modules::enabled('leads'))->toBeTrue();
    expect(Modules::enabled('invoices'))->toBeTrue();
});

test('every module is enabled when the config is an empty array', function () {
    config()->set('laravel-crm.modules', []);

    expect(Modules::enabled('leads'))->toBeTrue();
    expect(Modules::enabled('deals'))->toBeTrue();
    expect(Modules::enabled('quotes'))->toBeTrue();
    expect(Modules::enabled('orders'))->toBeTrue();
    expect(Modules::enabled('invoices'))->toBeTrue();
    expect(Modules::enabled('deliveries'))->toBeTrue();
    expect(Modules::enabled('purchase-orders'))->toBeTrue();
});

test('only the listed modules are enabled when the config is a populated array', function () {
    config()->set('laravel-crm.modules', ['leads']);

    expect(Modules::enabled('leads'))->toBeTrue();
    expect(Modules::enabled('deals'))->toBeFalse();
    expect(Modules::enabled('quotes'))->toBeFalse();
    expect(Modules::enabled('purchase-orders'))->toBeFalse();
});

test('anyEnabled is true when at least one slug is enabled', function () {
    config()->set('laravel-crm.modules', ['deals']);

    expect(Modules::anyEnabled(['leads', 'deals', 'orders']))->toBeTrue();
});

test('anyEnabled is false when no slug is enabled', function () {
    config()->set('laravel-crm.modules', ['leads']);

    expect(Modules::anyEnabled(['deals', 'orders', 'deliveries']))->toBeFalse();
});

test('anyEnabled with no slugs means no module requirement', function () {
    config()->set('laravel-crm.modules', ['leads']);

    expect(Modules::anyEnabled([]))->toBeTrue();
});

test('anyEnabled is true for every slug list when the config is an empty array', function () {
    config()->set('laravel-crm.modules', []);

    expect(Modules::anyEnabled(['purchase-orders']))->toBeTrue();
    expect(Modules::anyEnabled(['leads', 'deals', 'orders', 'deliveries']))->toBeTrue();
});
