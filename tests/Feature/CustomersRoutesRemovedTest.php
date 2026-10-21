<?php

/*
 * Customers were removed from the UI in v2, but the v1 /customers route group and
 * CustomerController were left registered. Opening /crm/customers called the v1
 * `sortable()` macro, which v2 no longer loads, and threw a BadMethodCallException
 * (#93). The routes, controller, view and form requests have been removed. This
 * file keeps them removed.
 *
 * The Customer model, observer and policy stay: the V1→V2 upgrade still migrates the
 * customers table and leads, deals, quotes and orders still have customer_id.
 */

use Illuminate\Support\Facades\Route;

test('no customers route is registered', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->getName() ?? '', 'laravel-crm.customers.')
            || str_contains($route->getActionName(), 'CustomerController'))
        ->map(fn ($route) => $route->getName() ?? $route->uri())
        ->values()
        ->all();

    expect($routes)->toBe([]);
});

test('the customer controller, view and form requests are gone', function () {
    expect(class_exists('VentureDrake\LaravelCrm\Http\Controllers\CustomerController'))->toBeFalse()
        ->and(class_exists('VentureDrake\LaravelCrm\Http\Requests\StoreClientRequest'))->toBeFalse()
        ->and(class_exists('VentureDrake\LaravelCrm\Http\Requests\UpdateClientRequest'))->toBeFalse()
        ->and(view()->exists('laravel-crm::customers.index'))->toBeFalse();
});

test('the customer model is kept for the v1 upgrade and existing relations', function () {
    expect(class_exists('VentureDrake\LaravelCrm\Models\Customer'))->toBeTrue();
});
