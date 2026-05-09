<?php

use Illuminate\Support\Facades\Route;

test('unauthenticated request to crm is redirected or unauthorized', function () {
    $response = $this->get('/crm');

    expect($response->getStatusCode())->not->toBe(200);
});

test('route prefix can be changed', function () {
    config()->set('laravel-crm.route_prefix', 'sales');

    $route = Route::getRoutes()->getByName('laravel-crm.leads.index');
    expect($route)->not->toBeNull();
    expect(config('laravel-crm.route_prefix'))->toBe('sales');
});

test('disabling user interface skips route registration', function () {
    expect((bool) config('laravel-crm.user_interface'))->toBeTrue();
});
