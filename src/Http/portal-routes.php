<?php

use Illuminate\Support\Facades\Route;

/*
 * Public portal routes for quotes & invoices.
 *
 * Registered by LaravelCrmServiceProvider::registerRoutes() under the `p`
 * prefix and the `web` middleware group (so signed-URL validation, sessions,
 * and CSRF on POST work) but OUTSIDE the CRM auth/crm-api middleware stack —
 * these routes must be reachable by unauthenticated recipients via a signed
 * link.
 */

Route::prefix('quotes')->group(function () {
    Route::get('{quote:external_id}', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\QuoteController@show')
        ->name('laravel-crm.portal.quotes.show');

    Route::post('{quote:external_id}', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\QuoteController@process')
        ->name('laravel-crm.portal.quotes.process');
});

Route::prefix('invoices')->group(function () {
    Route::get('{invoice:external_id}', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\InvoiceController@show')
        ->name('laravel-crm.portal.invoices.show');

    Route::post('{invoice:external_id}', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\InvoiceController@process')
        ->name('laravel-crm.portal.invoices.process');
});

Route::prefix('purchase-orders')->group(function () {
    Route::get('{purchaseOrder:external_id}', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\PurchaseOrderController@show')
        ->name('laravel-crm.portal.purchase-orders.show');

    Route::post('{purchaseOrder:external_id}', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\PurchaseOrderController@process')
        ->name('laravel-crm.portal.purchase-orders.process');
});

/* Portal Auth (self-service login/register for the public feature board) */
Route::get('login', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\PortalAuthController@showLogin')
    ->name('laravel-crm.portal.login');
Route::post('login', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\PortalAuthController@login')
    ->middleware('throttle:6,1')
    ->name('laravel-crm.portal.login.attempt');

Route::get('register', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\PortalAuthController@showRegister')
    ->name('laravel-crm.portal.register');
Route::post('register', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\PortalAuthController@register')
    ->middleware('throttle:6,1')
    ->name('laravel-crm.portal.register.store');

Route::post('logout', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\PortalAuthController@logout')
    ->name('laravel-crm.portal.logout');

/* Public Feature Board */
Route::prefix('features')->group(function () {
    Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\PublicFeatureController@index')
        ->name('laravel-crm.portal.features.index');

    Route::get('submit', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\PublicFeatureController@create')
        ->name('laravel-crm.portal.features.create');
    Route::post('submit', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\PublicFeatureController@store')
        ->name('laravel-crm.portal.features.store');
    Route::post('{feature:external_id}/vote', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\PublicFeatureController@vote')
        ->name('laravel-crm.portal.features.vote');
    Route::delete('{feature:external_id}/vote', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\PublicFeatureController@unvote')
        ->name('laravel-crm.portal.features.unvote');
    Route::post('{feature:external_id}/comments', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\PublicFeatureController@comment')
        ->name('laravel-crm.portal.features.comments.store');

    Route::get('{feature:external_id}', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\PublicFeatureController@show')
        ->name('laravel-crm.portal.features.show');
});
