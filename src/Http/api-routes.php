<?php

use Illuminate\Support\Facades\Route;
use VentureDrake\LaravelCrm\Http\Controllers\Api\V2\AuthController;
use VentureDrake\LaravelCrm\Http\Controllers\Api\V2\LeadController;
use VentureDrake\LaravelCrm\Http\Controllers\Api\V2\OrganizationController;
use VentureDrake\LaravelCrm\Http\Controllers\Api\V2\ProductController;

/*
 * Laravel CRM API routes (v2).
 *
 * This file is loaded by LaravelCrmServiceProvider::registerRoutes() under
 * the `api/crm/v2` prefix with the `api`, `laravel-crm.api.json`, and
 * `throttle:laravel-crm-api` middleware applied. Authenticated routes layer
 * `auth:sanctum`, `crm-api`, and `laravel-crm.api.team` on top.
 */

Route::post('auth/token', [AuthController::class, 'issueToken'])
    ->name('laravel-crm.api.v2.auth.token.issue');

Route::middleware(['auth:sanctum', 'crm-api', 'laravel-crm.api.team'])->group(function () {
    Route::get('auth/me', [AuthController::class, 'me'])
        ->name('laravel-crm.api.v2.auth.me');

    Route::delete('auth/token', [AuthController::class, 'revokeToken'])
        ->name('laravel-crm.api.v2.auth.token.revoke');

    Route::apiResource('leads', LeadController::class)
        ->names('laravel-crm.api.v2.leads')
        ->scoped(['lead' => 'external_id']);

    Route::apiResource('products', ProductController::class)
        ->names('laravel-crm.api.v2.products')
        ->scoped(['product' => 'external_id']);

    Route::apiResource('organizations', OrganizationController::class)
        ->names('laravel-crm.api.v2.organizations')
        ->scoped(['organization' => 'external_id']);
});
