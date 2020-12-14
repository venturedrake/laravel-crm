<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

/* Public Routes */

Route::get('login', function () {
    return View::make('laravel-crm::auth.login');
})->name('laravel-crm.login');

Route::post('login', function () {
    //
});

Route::post('logout', function () {
    //
})->name('laravel-crm.logout');

Route::get('register', function () {
    //
})->name('laravel-crm.register');

Route::post('register', function () {
    //
});

Route::get('password/reset', function () {
    //
})->name('laravel-crm.password.request');

Route::post('password/email', function () {
    //
});

Route::get('password/reset/{token}', function () {
    //
})->name('laravel-crm.password.reset');

Route::post('password/reset', function () {
    //
})->name('laravel-crm.password.update');

Route::get('password/confirm', function () {
    //
})->name('laravel-crm.password.confirm');

Route::get('password/confirm', function () {
    //
});

/* Private Routes */

Route::get('/', function () {
    return View::make('laravel-crm::index');
})->middleware('auth.laravel-crm')->name('laravel-crm.dashboard');

Route::get('leads', function () {
    return View::make('laravel-crm::leads.index');
})->middleware('auth.laravel-crm')->name('laravel-crm.leads');

Route::get('deals', function () {
    return View::make('laravel-crm::deals.index');
})->middleware('auth.laravel-crm')->name('laravel-crm.deals');

Route::get('activities', function () {
    return View::make('laravel-crm::activities.index');
})->middleware('auth.laravel-crm')->name('laravel-crm.activities');

Route::get('contacts', function () {
    return View::make('laravel-crm::contacts.index');
})->middleware('auth.laravel-crm')->name('laravel-crm.contacts');
