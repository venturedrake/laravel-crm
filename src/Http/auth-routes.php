<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use VentureDrake\LaravelCrm\Livewire\Auth\ForgotPassword;
use VentureDrake\LaravelCrm\Livewire\Auth\Login;
use VentureDrake\LaravelCrm\Livewire\Auth\ResetPassword;

/*
|--------------------------------------------------------------------------
| CRM Authentication Routes
|--------------------------------------------------------------------------
|
| Self-contained auth routes for the CRM package. These do NOT require
| the host Laravel app to have its own auth scaffolding (Fortify, Breeze, etc).
|
*/

Route::middleware('guest')->group(function () {
    Route::get('login', Login::class)
        ->name('laravel-crm.login');

    Route::get('password/reset', ForgotPassword::class)
        ->name('laravel-crm.password.request');

    Route::get('password/reset/{token}', ResetPassword::class)
        ->name('laravel-crm.password.reset');
});

Route::post('logout', function () {
    Auth::logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('laravel-crm.login');
})->name('laravel-crm.logout');
