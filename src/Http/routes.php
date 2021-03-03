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

Route::group(['prefix' => 'deals', 'middleware' => 'auth.laravel-crm'], function () {
    Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\DealController@index')
        ->name('laravel-crm.deals.index');

    Route::get('create', 'VentureDrake\LaravelCrm\Http\Controllers\DealController@create')
        ->name('laravel-crm.deals.create');

    Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\DealController@store')
        ->name('laravel-crm.deals.store');

    Route::get('{deal}', 'VentureDrake\LaravelCrm\Http\Controllers\DealController@show')
        ->name('laravel-crm.deals.show');

    Route::get('{deal}/edit', 'VentureDrake\LaravelCrm\Http\Controllers\DealController@edit')
        ->name('laravel-crm.deals.edit');

    Route::put('{deal}', 'VentureDrake\LaravelCrm\Http\Controllers\DealController@update')
        ->name('laravel-crm.deals.update');

    Route::delete('{deal}', 'VentureDrake\LaravelCrm\Http\Controllers\DealController@destroy')
        ->name('laravel-crm.deals.destroy');
});

/* Activities */

Route::get('activities', function () {
    return View::make('laravel-crm::activities.index');
})->middleware('auth.laravel-crm')->name('laravel-crm.activities.index');

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

/* Users */

Route::group(['prefix' => 'users', 'middleware' => 'auth.laravel-crm'], function () {
    Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\UserController@index')
        ->name('laravel-crm.users.index');

    Route::get('invite', 'VentureDrake\LaravelCrm\Http\Controllers\UserController@invite')
        ->name('laravel-crm.users.invite');

    Route::post('invite', 'VentureDrake\LaravelCrm\Http\Controllers\UserController@sendInvite')
        ->name('laravel-crm.users.sendinvite');

    Route::get('create', 'VentureDrake\LaravelCrm\Http\Controllers\UserController@create')
        ->name('laravel-crm.users.create');

    Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\UserController@store')
        ->name('laravel-crm.users.store');

    Route::get('{user}', 'VentureDrake\LaravelCrm\Http\Controllers\UserController@show')
        ->name('laravel-crm.users.show');

    Route::get('{user}/edit', 'VentureDrake\LaravelCrm\Http\Controllers\UserController@edit')
        ->name('laravel-crm.users.edit');

    Route::put('{user}', 'VentureDrake\LaravelCrm\Http\Controllers\UserController@update')
        ->name('laravel-crm.users.update');

    Route::delete('{user}', 'VentureDrake\LaravelCrm\Http\Controllers\UserController@destroy')
        ->name('laravel-crm.users.destroy');
});

/* Products */

Route::group(['prefix' => 'teams', 'middleware' => 'auth.laravel-crm'], function () {
    Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\TeamController@index')
        ->name('laravel-crm.teams.index');

    Route::get('create', 'VentureDrake\LaravelCrm\Http\Controllers\TeamController@create')
        ->name('laravel-crm.teams.create');

    Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\TeamController@store')
        ->name('laravel-crm.teams.store');

    Route::get('{team}', 'VentureDrake\LaravelCrm\Http\Controllers\TeamController@show')
        ->name('laravel-crm.teams.show');

    Route::get('{team}/edit', 'VentureDrake\LaravelCrm\Http\Controllers\TeamController@edit')
        ->name('laravel-crm.teams.edit');

    Route::put('{team}', 'VentureDrake\LaravelCrm\Http\Controllers\TeamController@update')
        ->name('laravel-crm.teams.update');

    Route::delete('{team}', 'VentureDrake\LaravelCrm\Http\Controllers\TeamController@destroy')
        ->name('laravel-crm.teams.destroy');
});

/* Products */

Route::group(['prefix' => 'products', 'middleware' => 'auth.laravel-crm'], function () {
    Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\ProductController@index')
        ->name('laravel-crm.products.index');

    Route::get('create', 'VentureDrake\LaravelCrm\Http\Controllers\ProductController@create')
        ->name('laravel-crm.products.create');

    Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\ProductController@store')
        ->name('laravel-crm.products.store');

    Route::get('{product}', 'VentureDrake\LaravelCrm\Http\Controllers\ProductController@show')
        ->name('laravel-crm.products.show');

    Route::get('{product}/edit', 'VentureDrake\LaravelCrm\Http\Controllers\ProductController@edit')
        ->name('laravel-crm.products.edit');

    Route::put('{product}', 'VentureDrake\LaravelCrm\Http\Controllers\ProductController@update')
        ->name('laravel-crm.products.update');

    Route::delete('{product}', 'VentureDrake\LaravelCrm\Http\Controllers\ProductController@destroy')
        ->name('laravel-crm.products.destroy');
});
