<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

/* Public Routes */

Route::get('crm-login', function () {
    return redirect(route('login'));

    return View::make('laravel-crm::auth.login');
})->name('laravel-crm.login');

Route::post('crm-login', function () {
    //
});

Route::post('crm-logout', function () {
    //
})->name('laravel-crm.logout');

Route::get('crm-register', function () {
    return redirect(route('register'));
})->name('laravel-crm.register');

Route::post('crm-register', function () {
    //
});

Route::get('crm-password/reset', function () {
    //
})->name('laravel-crm.password.request');

Route::post('crm-password/email', function () {
    //
});

Route::get('crm-password/reset/{token}', function () {
    //
})->name('laravel-crm.password.reset');

Route::post('crm-password/reset', function () {
    //
})->name('laravel-crm.password.update');

Route::get('crm-password/confirm', function () {
    //
})->name('laravel-crm.password.confirm');

Route::get('crm-password/confirm', function () {
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
        ->name('laravel-crm.leads.index')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Lead']);

    Route::get('create', 'VentureDrake\LaravelCrm\Http\Controllers\LeadController@create')
        ->name('laravel-crm.leads.create')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\Lead']);

    Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\LeadController@store')
        ->name('laravel-crm.leads.store')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\Lead']);

    Route::get('{lead}', 'VentureDrake\LaravelCrm\Http\Controllers\LeadController@show')
        ->name('laravel-crm.leads.show')
        ->middleware(['can:view,lead']);

    Route::get('{lead}/edit', 'VentureDrake\LaravelCrm\Http\Controllers\LeadController@edit')
        ->name('laravel-crm.leads.edit')
        ->middleware(['can:update,lead']);

    Route::put('{lead}', 'VentureDrake\LaravelCrm\Http\Controllers\LeadController@update')
        ->name('laravel-crm.leads.update')
        ->middleware(['can:update,lead']);

    Route::delete('{lead}', 'VentureDrake\LaravelCrm\Http\Controllers\LeadController@destroy')
        ->name('laravel-crm.leads.destroy')
        ->middleware(['can:delete,lead']);

    Route::post('search', 'VentureDrake\LaravelCrm\Http\Controllers\LeadController@search')
        ->name('laravel-crm.leads.search')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Lead']);

    Route::get('{lead}/convert', 'VentureDrake\LaravelCrm\Http\Controllers\LeadController@convertToDeal')
        ->name('laravel-crm.leads.convert-to-deal')
        ->middleware(['can:update,lead']);

    Route::post('{lead}/convert', 'VentureDrake\LaravelCrm\Http\Controllers\LeadController@storeAsDeal')
        ->name('laravel-crm.leads.store-as-deal')
        ->middleware(['can:update,lead']);
});

/* Deals */

Route::group(['prefix' => 'deals', 'middleware' => 'auth.laravel-crm'], function () {
    Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\DealController@index')
        ->name('laravel-crm.deals.index')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Deal']);

    Route::get('create/{model?}/{id?}', 'VentureDrake\LaravelCrm\Http\Controllers\DealController@create')
        ->name('laravel-crm.deals.create')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\Deal']);

    Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\DealController@store')
        ->name('laravel-crm.deals.store')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\Deal']);

    Route::get('{deal}', 'VentureDrake\LaravelCrm\Http\Controllers\DealController@show')
        ->name('laravel-crm.deals.show')
        ->middleware(['can:view,deal']);

    Route::get('{deal}/edit', 'VentureDrake\LaravelCrm\Http\Controllers\DealController@edit')
        ->name('laravel-crm.deals.edit')
        ->middleware(['can:update,deal']);

    Route::put('{deal}', 'VentureDrake\LaravelCrm\Http\Controllers\DealController@update')
        ->name('laravel-crm.deals.update')
        ->middleware(['can:update,deal']);

    Route::delete('{deal}', 'VentureDrake\LaravelCrm\Http\Controllers\DealController@destroy')
        ->name('laravel-crm.deals.destroy')
        ->middleware(['can:delete,deal']);

    Route::post('search', 'VentureDrake\LaravelCrm\Http\Controllers\DealController@search')
        ->name('laravel-crm.deals.search')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Deal']);

    Route::get('{deal}/won', 'VentureDrake\LaravelCrm\Http\Controllers\DealController@won')
        ->name('laravel-crm.deals.won')
        ->middleware(['can:update,deal']);

    Route::get('{deal}/lost', 'VentureDrake\LaravelCrm\Http\Controllers\DealController@lost')
        ->name('laravel-crm.deals.lost')
        ->middleware(['can:update,deal']);

    Route::get('{deal}/reopen', 'VentureDrake\LaravelCrm\Http\Controllers\DealController@reopen')
        ->name('laravel-crm.deals.reopen')
        ->middleware(['can:update,deal']);

    /* Deal Products */

    Route::group(['prefix' => '{deal}/products', 'middleware' => 'auth.laravel-crm'], function () {
        Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\DealProductController@index')
            ->name('laravel-crm.deal-products.index');

        Route::get('create', 'VentureDrake\LaravelCrm\Http\Controllers\DealProductController@create')
            ->name('laravel-crm.deal-products.create');

        Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\DealProductController@store')
            ->name('laravel-crm.deal-products.store');

        Route::get('{product}', 'VentureDrake\LaravelCrm\Http\Controllers\DealProductController@show')
            ->name('laravel-crm.deal-products.show');

        Route::get('{product}/edit', 'VentureDrake\LaravelCrm\Http\Controllers\DealProductController@edit')
            ->name('laravel-crm.deal-products.edit');

        Route::put('{product}', 'VentureDrake\LaravelCrm\Http\Controllers\DealProductController@update')
            ->name('laravel-crm.deal-products.update');

        Route::delete('{product}', 'VentureDrake\LaravelCrm\Http\Controllers\DealProductController@destroy')
            ->name('laravel-crm.deal-products.destroy');
    });
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
        ->name('laravel-crm.people.index')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Person']);

    Route::get('create/{model?}/{id?}', 'VentureDrake\LaravelCrm\Http\Controllers\PersonController@create')
        ->name('laravel-crm.people.create')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\Person']);

    Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\PersonController@store')
        ->name('laravel-crm.people.store')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\Person']);
    

    Route::get('{person}', 'VentureDrake\LaravelCrm\Http\Controllers\PersonController@show')
        ->name('laravel-crm.people.show')
        ->middleware(['can:view,person']);

    Route::get('{person}/edit', 'VentureDrake\LaravelCrm\Http\Controllers\PersonController@edit')
        ->name('laravel-crm.people.edit')
        ->middleware(['can:update,person']);

    Route::put('{person}', 'VentureDrake\LaravelCrm\Http\Controllers\PersonController@update')
        ->name('laravel-crm.people.update')
        ->middleware(['can:update,person']);

    Route::delete('{person}', 'VentureDrake\LaravelCrm\Http\Controllers\PersonController@destroy')
        ->name('laravel-crm.people.destroy')
        ->middleware(['can:delete,person']);

    Route::post('search', 'VentureDrake\LaravelCrm\Http\Controllers\PersonController@search')
        ->name('laravel-crm.people.search')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Person']);

    Route::get('{person}/autocomplete', 'VentureDrake\LaravelCrm\Http\Controllers\PersonController@autocomplete')
        ->name('laravel-crm.people.autocomplete')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Person']);
});

/* Organisations */

Route::group(['prefix' => 'organisations', 'middleware' => 'auth.laravel-crm'], function () {
    Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\OrganisationController@index')
        ->name('laravel-crm.organisations.index')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Organisation']);

    Route::get('create', 'VentureDrake\LaravelCrm\Http\Controllers\OrganisationController@create')
        ->name('laravel-crm.organisations.create')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\Organisation']);

    Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\OrganisationController@store')
        ->name('laravel-crm.organisations.store')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\Organisation']);

    Route::get('{organisation}', 'VentureDrake\LaravelCrm\Http\Controllers\OrganisationController@show')
        ->name('laravel-crm.organisations.show')
        ->middleware(['can:view,organisation']);

    Route::get('{organisation}/edit', 'VentureDrake\LaravelCrm\Http\Controllers\OrganisationController@edit')
        ->name('laravel-crm.organisations.edit')
        ->middleware(['can:update,organisation']);

    Route::put('{organisation}', 'VentureDrake\LaravelCrm\Http\Controllers\OrganisationController@update')
        ->name('laravel-crm.organisations.update')
        ->middleware(['can:update,organisation']);

    Route::delete('{organisation}', 'VentureDrake\LaravelCrm\Http\Controllers\OrganisationController@destroy')
        ->name('laravel-crm.organisations.destroy')
        ->middleware(['can:delete,organisation']);
    
    Route::post('search', 'VentureDrake\LaravelCrm\Http\Controllers\OrganisationController@search')
        ->name('laravel-crm.organisations.search')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Organisation']);

    Route::get('{organisation}/autocomplete', 'VentureDrake\LaravelCrm\Http\Controllers\OrganisationController@autocomplete')
        ->name('laravel-crm.organisations.autocomplete')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Organisation']);
});

/* Users */

Route::group(['prefix' => 'users', 'middleware' => 'auth.laravel-crm'], function () {
    Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\UserController@index')
        ->name('laravel-crm.users.index')
        ->middleware(['can:viewAny,App\User']);

    Route::get('invite', 'VentureDrake\LaravelCrm\Http\Controllers\UserController@invite')
        ->name('laravel-crm.users.invite')
        ->middleware(['can:create,App\User']);

    Route::post('invite', 'VentureDrake\LaravelCrm\Http\Controllers\UserController@sendInvite')
        ->name('laravel-crm.users.sendinvite')
        ->middleware(['can:create,App\User']);

    Route::get('create', 'VentureDrake\LaravelCrm\Http\Controllers\UserController@create')
        ->name('laravel-crm.users.create')
        ->middleware(['can:create,App\User']);

    Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\UserController@store')
        ->name('laravel-crm.users.store')
        ->middleware(['can:create,App\User']);

    Route::get('{user}', 'VentureDrake\LaravelCrm\Http\Controllers\UserController@show')
        ->name('laravel-crm.users.show')
        ->middleware(['can:view,user']);

    Route::get('{user}/edit', 'VentureDrake\LaravelCrm\Http\Controllers\UserController@edit')
        ->name('laravel-crm.users.edit')
        ->middleware(['can:update,user']);

    Route::put('{user}', 'VentureDrake\LaravelCrm\Http\Controllers\UserController@update')
        ->name('laravel-crm.users.update')
        ->middleware(['can:update,user']);

    Route::delete('{user}', 'VentureDrake\LaravelCrm\Http\Controllers\UserController@destroy')
        ->name('laravel-crm.users.destroy')
        ->middleware(['can:delete,user']);
});

/* Teams */

Route::group(['prefix' => 'crm-teams', 'middleware' => 'auth.laravel-crm'], function () {
    Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\TeamController@index')
        ->name('laravel-crm.teams.index')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Team']);

    Route::get('create', 'VentureDrake\LaravelCrm\Http\Controllers\TeamController@create')
        ->name('laravel-crm.teams.create')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\Team']);

    Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\TeamController@store')
        ->name('laravel-crm.teams.store')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\Team']);

    Route::get('{team}', 'VentureDrake\LaravelCrm\Http\Controllers\TeamController@show')
        ->name('laravel-crm.teams.show')
        ->middleware(['can:view,team']);

    Route::get('{team}/edit', 'VentureDrake\LaravelCrm\Http\Controllers\TeamController@edit')
        ->name('laravel-crm.teams.edit')
        ->middleware(['can:update,team']);

    Route::put('{team}', 'VentureDrake\LaravelCrm\Http\Controllers\TeamController@update')
        ->name('laravel-crm.teams.update')
        ->middleware(['can:update,team']);

    Route::delete('{team}', 'VentureDrake\LaravelCrm\Http\Controllers\TeamController@destroy')
        ->name('laravel-crm.teams.destroy')
        ->middleware(['can:delete,team']);
});

/* Products */

Route::group(['prefix' => 'products', 'middleware' => 'auth.laravel-crm'], function () {
    Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\ProductController@index')
        ->name('laravel-crm.products.index')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Product']);

    Route::get('create', 'VentureDrake\LaravelCrm\Http\Controllers\ProductController@create')
        ->name('laravel-crm.products.create')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\Product']);

    Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\ProductController@store')
        ->name('laravel-crm.products.store')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\Product']);

    Route::get('{product}', 'VentureDrake\LaravelCrm\Http\Controllers\ProductController@show')
        ->name('laravel-crm.products.show')
        ->middleware(['can:view,product']);

    Route::get('{product}/edit', 'VentureDrake\LaravelCrm\Http\Controllers\ProductController@edit')
        ->name('laravel-crm.products.edit')
        ->middleware(['can:update,product']);

    Route::put('{product}', 'VentureDrake\LaravelCrm\Http\Controllers\ProductController@update')
        ->name('laravel-crm.products.update')
        ->middleware(['can:update,product']);

    Route::delete('{product}', 'VentureDrake\LaravelCrm\Http\Controllers\ProductController@destroy')
        ->name('laravel-crm.products.destroy')
        ->middleware(['can:delete,product']);

    Route::post('search', 'VentureDrake\LaravelCrm\Http\Controllers\ProductController@search')
        ->name('laravel-crm.products.search')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Product']);

    Route::get('{product}/autocomplete', 'VentureDrake\LaravelCrm\Http\Controllers\ProductController@autocomplete')
        ->name('laravel-crm.products.autocomplete')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Product']);
});

/* Settings */

Route::group(['prefix' => 'settings', 'middleware' => 'auth.laravel-crm'], function () {
    Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\SettingController@edit')
        ->name('laravel-crm.settings.edit')
        ->middleware(['can:update,VentureDrake\LaravelCrm\Models\Setting']);

    Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\SettingController@update')
        ->name('laravel-crm.settings.update')
        ->middleware(['can:update,VentureDrake\LaravelCrm\Models\Setting']);
});

/* Updates */
Route::group(['prefix' => 'updates', 'middleware' => 'auth.laravel-crm'], function () {
    Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\UpdateController@index')
        ->name('laravel-crm.updates.index');
});


/* Roles */
Route::group(['prefix' => 'roles', 'middleware' => 'auth.laravel-crm'], function () {
    Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\RoleController@index')
        ->name('laravel-crm.roles.index')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Role']);

    Route::get('create', 'VentureDrake\LaravelCrm\Http\Controllers\RoleController@create')
        ->name('laravel-crm.roles.create')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\Role']);

    Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\RoleController@store')
        ->name('laravel-crm.roles.store')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\Role']);

    Route::get('{role}', 'VentureDrake\LaravelCrm\Http\Controllers\RoleController@show')
        ->name('laravel-crm.roles.show')
        ->middleware(['can:view,role']);

    Route::get('{role}/edit', 'VentureDrake\LaravelCrm\Http\Controllers\RoleController@edit')
        ->name('laravel-crm.roles.edit')
        ->middleware(['can:update,role']);

    Route::put('{role}', 'VentureDrake\LaravelCrm\Http\Controllers\RoleController@update')
        ->name('laravel-crm.roles.update')
        ->middleware(['can:update,role']);

    Route::delete('{role}', 'VentureDrake\LaravelCrm\Http\Controllers\RoleController@destroy')
        ->name('laravel-crm.roles.destroy')
        ->middleware(['can:delete,role']);
});

/* CRM routes (AJAX) */
Route::group(['prefix' => 'crm', 'middleware' => 'auth.laravel-crm'], function () {
    Route::group(['prefix' => 'people', 'middleware' => 'auth.laravel-crm'], function () {
        Route::get('{person}/autocomplete', 'VentureDrake\LaravelCrm\Http\Controllers\PersonController@autocomplete')
            ->name('laravel-crm.people.autocomplete')
            ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Person']);
    });


    Route::group(['prefix' => 'organisations', 'middleware' => 'auth.laravel-crm'], function () {
        Route::get('{organisation}/autocomplete', 'VentureDrake\LaravelCrm\Http\Controllers\OrganisationController@autocomplete')
            ->name('laravel-crm.organisations.autocomplete')
            ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Organisation']);
    });

    Route::group(['prefix' => 'products', 'middleware' => 'auth.laravel-crm'], function () {
        Route::get('{product}/autocomplete', 'VentureDrake\LaravelCrm\Http\Controllers\ProductController@autocomplete')
            ->name('laravel-crm.products.autocomplete')
            ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Product']);
    });
});
