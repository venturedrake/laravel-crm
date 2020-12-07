<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

Route::get(config('laravel-crm.route'), function () {
    return View::make('laravel-crm::index');
})->name('laravel-crm.dashboard');
