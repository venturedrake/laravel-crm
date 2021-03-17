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

/* Dashboard */
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

    Route::post('search', 'VentureDrake\LaravelCrm\Http\Controllers\LeadController@search')
        ->name('laravel-crm.leads.search');

    Route::get('{lead}/convert', 'VentureDrake\LaravelCrm\Http\Controllers\LeadController@convertToDeal')
        ->name('laravel-crm.leads.convert-to-deal');

    Route::post('{lead}/convert', 'VentureDrake\LaravelCrm\Http\Controllers\LeadController@storeAsDeal')
        ->name('laravel-crm.leads.store-as-deal');
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

    Route::post('search', 'VentureDrake\LaravelCrm\Http\Controllers\DealController@search')
        ->name('laravel-crm.deals.search');

    Route::get('{deal}/won', 'VentureDrake\LaravelCrm\Http\Controllers\DealController@won')
        ->name('laravel-crm.deals.won');

    Route::get('{deal}/lost', 'VentureDrake\LaravelCrm\Http\Controllers\DealController@lost')
        ->name('laravel-crm.deals.lost');

    Route::get('{deal}/reopen', 'VentureDrake\LaravelCrm\Http\Controllers\DealController@reopen')
        ->name('laravel-crm.deals.reopen');
});

/* Activities */

Route::group(['prefix' => 'activities', 'middleware' => 'auth.laravel-crm'], function () {
    Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\ActivityController@index')
        ->name('laravel-crm.activities.index');

    Route::get('create', 'VentureDrake\LaravelCrm\Http\Controllers\ActivityController@create')
        ->name('laravel-crm.activities.create');

    Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\ActivityController@store')
        ->name('laravel-crm.activities.store');

    Route::get('{activity}', 'VentureDrake\LaravelCrm\Http\Controllers\ActivityController@show')
        ->name('laravel-crm.activities.show');

    Route::get('{activity}/edit', 'VentureDrake\LaravelCrm\Http\Controllers\ActivityController@edit')
        ->name('laravel-crm.activities.edit');

    Route::put('{activity}', 'VentureDrake\LaravelCrm\Http\Controllers\ActivityController@update')
        ->name('laravel-crm.activities.update');

    Route::delete('{activity}', 'VentureDrake\LaravelCrm\Http\Controllers\ActivityController@destroy')
        ->name('laravel-crm.activities.destroy');
});

/* Notes */

Route::group(['prefix' => 'notes', 'middleware' => 'auth.laravel-crm'], function () {
    Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\NoteController@index')
        ->name('laravel-crm.notes.index');

    Route::get('create', 'VentureDrake\LaravelCrm\Http\Controllers\NoteController@create')
        ->name('laravel-crm.notes.create');

    Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\NoteController@store')
        ->name('laravel-crm.notes.store');

    Route::get('{note}', 'VentureDrake\LaravelCrm\Http\Controllers\NoteController@show')
        ->name('laravel-crm.notes.show');

    Route::get('{note}/edit', 'VentureDrake\LaravelCrm\Http\Controllers\NoteController@edit')
        ->name('laravel-crm.notes.edit');

    Route::put('{note}', 'VentureDrake\LaravelCrm\Http\Controllers\NoteController@update')
        ->name('laravel-crm.notes.update');

    Route::delete('{note}', 'VentureDrake\LaravelCrm\Http\Controllers\NoteController@destroy')
        ->name('laravel-crm.notes.destroy');
});

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

    Route::post('search', 'VentureDrake\LaravelCrm\Http\Controllers\PersonController@search')
        ->name('laravel-crm.people.search');

    Route::get('{person}/autocomplete', 'VentureDrake\LaravelCrm\Http\Controllers\PersonController@autocomplete')
        ->name('laravel-crm.people.autocomplete');
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


    Route::post('search', 'VentureDrake\LaravelCrm\Http\Controllers\OrganisationController@search')
        ->name('laravel-crm.organisations.search');

    Route::get('{organisation}/autocomplete', 'VentureDrake\LaravelCrm\Http\Controllers\OrganisationController@autocomplete')
        ->name('laravel-crm.organisations.autocomplete');
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

/* Teams */

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

    Route::post('search', 'VentureDrake\LaravelCrm\Http\Controllers\ProductController@search')
        ->name('laravel-crm.products.search');
});
