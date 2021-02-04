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

/* Dashboarh */
Route::get('/', 'VentureDrake\LaravelCrm\Http\Controllers\DashboardController@index')
    ->middleware('auth.laravel-crm')
    ->name('laravel-crm.dashboard');

/* Leads */

Route::group(['prefix' => 'leads','middleware' => 'auth.laravel-crm'], function () {
    Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\LeadController@index')
        ->name('laravel-crm.leads.index');

    Route::get('create', 'VentureDrake\LaravelCrm\Http\Controllers\LeadController@create')
        ->name('laravel-crm.leads.create');

    Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\LeadController@store')
        ->name('laravel-crm.leads.store');

    Route::get('{lead}', 'VentureDrake\LaravelCrm\Http\Controllers\LeadController@show')
        ->name('laravel-crm.leads.show');

    Route::get('{lead}/edit', 'VentureDrake\LaravelCrm\Http\Controllers\LeadController@edit')
        ->name('laravel-crm.leads.edit');

    Route::put('{lead}', 'VentureDrake\LaravelCrm\Http\Controllers\LeadController@update')
        ->name('laravel-crm.leads.update');

    Route::delete('{lead}', 'VentureDrake\LaravelCrm\Http\Controllers\LeadController@destroy')
        ->name('laravel-crm.leads.destroy');
});

/* Deals */

Route::get('deals', function () {
    return View::make('laravel-crm::deals.index');
})->middleware('auth.laravel-crm')->name('laravel-crm.deals.index');

/* Activities */

Route::get('activities', function () {
    return View::make('laravel-crm::activities.index');
})->middleware('auth.laravel-crm')->name('laravel-crm.activities.index');

/* Contacts */

Route::get('contacts', function () {
    return View::make('laravel-crm::contacts.index');
})->middleware('auth.laravel-crm')->name('laravel-crm.contacts.index');

/* People */

Route::group(['prefix' => 'people', 'middleware' => 'auth.laravel-crm'], function () {
    Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\PersonController@index')
        ->name('laravel-crm.people.index');

    Route::get('create', 'VentureDrake\LaravelCrm\Http\Controllers\PersonController@create')
        ->name('laravel-crm.people.create');

    Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\PersonController@store')
        ->name('laravel-crm.people.store');

    Route::get('{person}', 'VentureDrake\LaravelCrm\Http\Controllers\PersonController@show')
        ->name('laravel-crm.people.show');

    Route::get('{person}/edit', 'VentureDrake\LaravelCrm\Http\Controllers\PersonController@edit')
        ->name('laravel-crm.people.edit');

    Route::put('{person}', 'VentureDrake\LaravelCrm\Http\Controllers\PersonController@update')
        ->name('laravel-crm.people.update');

    Route::delete('{person}', 'VentureDrake\LaravelCrm\Http\Controllers\PersonController@destroy')
        ->name('laravel-crm.people.destroy');
});

/* Organisations */

Route::group(['prefix' => 'organisations', 'middleware' => 'auth.laravel-crm'], function () {
    Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\OrganisationController@index')
        ->name('laravel-crm.organisations.index');

    Route::get('create', 'VentureDrake\LaravelCrm\Http\Controllers\OrganisationController@create')
        ->name('laravel-crm.organisations.create');

    Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\OrganisationController@store')
        ->name('laravel-crm.organisations.store');

    Route::get('{organisation}', 'VentureDrake\LaravelCrm\Http\Controllers\OrganisationController@show')
        ->name('laravel-crm.organisations.show');

    Route::get('{organisation}/edit', 'VentureDrake\LaravelCrm\Http\Controllers\OrganisationController@edit')
        ->name('laravel-crm.organisations.edit');

    Route::put('{organisation}', 'VentureDrake\LaravelCrm\Http\Controllers\OrganisationController@update')
        ->name('laravel-crm.organisations.update');

    Route::delete('{organisation}', 'VentureDrake\LaravelCrm\Http\Controllers\OrganisationController@destroy')
        ->name('laravel-crm.organisations.destroy');
});
