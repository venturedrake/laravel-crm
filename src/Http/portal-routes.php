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
