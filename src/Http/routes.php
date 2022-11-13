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

Route::group(['prefix' => 'p'], function () {
    Route::prefix('quotes')->group(function () {
        Route::get('{quote:external_id}', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\QuoteController@show')
            ->name('laravel-crm.portal.quotes.show');
    });
});

/* Private Routes */

/* Dashboard */

Route::get('/', 'VentureDrake\LaravelCrm\Http\Controllers\DashboardController@index')
    ->middleware('auth.laravel-crm')
    ->name('laravel-crm.dashboard');

/* Leads */

Route::group(['prefix' => 'leads','middleware' => 'auth.laravel-crm'], function () {
    Route::any('filter', 'VentureDrake\LaravelCrm\Http\Controllers\LeadController@index')
        ->name('laravel-crm.leads.filter')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Lead']);
    
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
    Route::get('create-product', 'VentureDrake\LaravelCrm\Http\Controllers\DealProductController@createProduct')
        ->name('laravel-crm.deal-products.create-product');

    Route::any('filter', 'VentureDrake\LaravelCrm\Http\Controllers\DealController@index')
        ->name('laravel-crm.deals.filter')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Deal']);
    
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

/* Quotes */

Route::group(['prefix' => 'quotes', 'middleware' => 'auth.laravel-crm'], function () {
    Route::get('create-product', 'VentureDrake\LaravelCrm\Http\Controllers\QuoteProductController@createProduct')
        ->name('laravel-crm.quote-products.create-product');

    Route::any('filter', 'VentureDrake\LaravelCrm\Http\Controllers\QuoteController@index')
        ->name('laravel-crm.quotes.filter')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Quote']);

    Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\QuoteController@index')
        ->name('laravel-crm.quotes.index')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Quote']);

    Route::get('create/{model?}/{id?}', 'VentureDrake\LaravelCrm\Http\Controllers\QuoteController@create')
        ->name('laravel-crm.quotes.create')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\Quote']);

    Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\QuoteController@store')
        ->name('laravel-crm.quotes.store')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\Quote']);

    Route::get('{quote}', 'VentureDrake\LaravelCrm\Http\Controllers\QuoteController@show')
        ->name('laravel-crm.quotes.show')
        ->middleware(['can:view,quote']);

    Route::get('{quote}/edit', 'VentureDrake\LaravelCrm\Http\Controllers\QuoteController@edit')
        ->name('laravel-crm.quotes.edit')
        ->middleware(['can:update,quote']);

    Route::put('{quote}', 'VentureDrake\LaravelCrm\Http\Controllers\QuoteController@update')
        ->name('laravel-crm.quotes.update')
        ->middleware(['can:update,quote']);

    Route::delete('{quote}', 'VentureDrake\LaravelCrm\Http\Controllers\QuoteController@destroy')
        ->name('laravel-crm.quotes.destroy')
        ->middleware(['can:delete,quote']);

    Route::post('search', 'VentureDrake\LaravelCrm\Http\Controllers\QuoteController@search')
        ->name('laravel-crm.quotes.search')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Quote']);

    Route::get('{quote}/accept', 'VentureDrake\LaravelCrm\Http\Controllers\QuoteController@accept')
        ->name('laravel-crm.quotes.accept')
        ->middleware(['can:update,quote']);

    Route::get('{quote}/reject', 'VentureDrake\LaravelCrm\Http\Controllers\QuoteController@reject')
        ->name('laravel-crm.quotes.reject')
        ->middleware(['can:update,quote']);

    Route::get('{quote}/unaccept', 'VentureDrake\LaravelCrm\Http\Controllers\QuoteController@unaccept')
        ->name('laravel-crm.quotes.unaccept')
        ->middleware(['can:update,quote']);

    Route::post('{quote}/send', 'VentureDrake\LaravelCrm\Http\Controllers\QuoteController@send')
        ->name('laravel-crm.quotes.send')
        ->middleware(['can:update,quote']);

    /* Quote Products */

    Route::group(['prefix' => '{quote}/products', 'middleware' => 'auth.laravel-crm'], function () {
        Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\QuoteProductController@index')
            ->name('laravel-crm.quote-products.index');

        Route::get('create', 'VentureDrake\LaravelCrm\Http\Controllers\QuoteProductController@create')
            ->name('laravel-crm.quote-products.create');

        Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\QuoteProductController@store')
            ->name('laravel-crm.quote-products.store');

        Route::get('{product}', 'VentureDrake\LaravelCrm\Http\Controllers\QuoteProductController@show')
            ->name('laravel-crm.quote-products.show');

        Route::get('{product}/edit', 'VentureDrake\LaravelCrm\Http\Controllers\QuoteProductController@edit')
            ->name('laravel-crm.quote-products.edit');

        Route::put('{product}', 'VentureDrake\LaravelCrm\Http\Controllers\QuoteProductController@update')
            ->name('laravel-crm.quote-products.update');

        Route::delete('{product}', 'VentureDrake\LaravelCrm\Http\Controllers\QuoteProductController@destroy')
            ->name('laravel-crm.quote-products.destroy');
    });
});

/* Orders */

Route::group(['prefix' => 'orders', 'middleware' => 'auth.laravel-crm'], function () {
    Route::get('create-product', 'VentureDrake\LaravelCrm\Http\Controllers\OrderProductController@createProduct')
        ->name('laravel-crm.order-products.create-product');

    Route::any('filter', 'VentureDrake\LaravelCrm\Http\Controllers\OrderController@index')
        ->name('laravel-crm.orders.filter')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Order']);

    Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\OrderController@index')
        ->name('laravel-crm.orders.index')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Order']);

    Route::get('create/{model?}/{id?}', 'VentureDrake\LaravelCrm\Http\Controllers\OrderController@create')
        ->name('laravel-crm.orders.create')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\Order']);

    Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\OrderController@store')
        ->name('laravel-crm.orders.store')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\Order']);

    Route::get('{order}', 'VentureDrake\LaravelCrm\Http\Controllers\OrderController@show')
        ->name('laravel-crm.orders.show')
        ->middleware(['can:view,order']);

    Route::get('{order}/edit', 'VentureDrake\LaravelCrm\Http\Controllers\OrderController@edit')
        ->name('laravel-crm.orders.edit')
        ->middleware(['can:update,order']);

    Route::put('{order}', 'VentureDrake\LaravelCrm\Http\Controllers\OrderController@update')
        ->name('laravel-crm.orders.update')
        ->middleware(['can:update,order']);

    Route::delete('{order}', 'VentureDrake\LaravelCrm\Http\Controllers\OrderController@destroy')
        ->name('laravel-crm.orders.destroy')
        ->middleware(['can:delete,order']);

    Route::post('search', 'VentureDrake\LaravelCrm\Http\Controllers\OrderController@search')
        ->name('laravel-crm.orders.search')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Order']);

    Route::get('{order}/accept', 'VentureDrake\LaravelCrm\Http\Controllers\OrderController@accept')
        ->name('laravel-crm.orders.accept')
        ->middleware(['can:update,order']);

    Route::get('{order}/reject', 'VentureDrake\LaravelCrm\Http\Controllers\OrderController@reject')
        ->name('laravel-crm.orders.reject')
        ->middleware(['can:update,order']);

    Route::get('{order}/unaccept', 'VentureDrake\LaravelCrm\Http\Controllers\OrderController@unaccept')
        ->name('laravel-crm.orders.unaccept')
        ->middleware(['can:update,order']);

    /* Order Products */

    Route::group(['prefix' => '{order}/products', 'middleware' => 'auth.laravel-crm'], function () {
        Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\OrderProductController@index')
            ->name('laravel-crm.order-products.index');

        Route::get('create', 'VentureDrake\LaravelCrm\Http\Controllers\OrderProductController@create')
            ->name('laravel-crm.order-products.create');

        Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\OrderProductController@store')
            ->name('laravel-crm.order-products.store');

        Route::get('{product}', 'VentureDrake\LaravelCrm\Http\Controllers\OrderProductController@show')
            ->name('laravel-crm.order-products.show');

        Route::get('{product}/edit', 'VentureDrake\LaravelCrm\Http\Controllers\OrderProductController@edit')
            ->name('laravel-crm.order-products.edit');

        Route::put('{product}', 'VentureDrake\LaravelCrm\Http\Controllers\OrderProductController@update')
            ->name('laravel-crm.order-products.update');

        Route::delete('{product}', 'VentureDrake\LaravelCrm\Http\Controllers\OrderProductController@destroy')
            ->name('laravel-crm.order-products.destroy');
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

/* Tasks */

Route::group(['prefix' => 'tasks', 'middleware' => 'auth.laravel-crm'], function () {
    Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\TaskController@index')
        ->name('laravel-crm.tasks.index')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Task']);

    /*Route::get('create', 'VentureDrake\LaravelCrm\Http\Controllers\TaskController@create')
        ->name('laravel-crm.tasks.create')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\Task']);

    Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\TaskController@store')
        ->name('laravel-crm.tasks.store')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\Task']);

    Route::get('{task}', 'VentureDrake\LaravelCrm\Http\Controllers\TaskController@show')
        ->name('laravel-crm.tasks.show')
        ->middleware(['can:view,task']);

    Route::get('{task}/edit', 'VentureDrake\LaravelCrm\Http\Controllers\TaskController@edit')
        ->name('laravel-crm.tasks.edit')
        ->middleware(['can:update,task']);

    Route::put('{task}', 'VentureDrake\LaravelCrm\Http\Controllers\TaskController@update')
        ->name('laravel-crm.tasks.update')
        ->middleware(['can:update,task']);*/

    Route::delete('{task}', 'VentureDrake\LaravelCrm\Http\Controllers\TaskController@destroy')
        ->name('laravel-crm.tasks.destroy')
        ->middleware(['can:delete,task']);

    /*Route::post('search', 'VentureDrake\LaravelCrm\Http\Controllers\TaskController@search')
        ->name('laravel-crm.tasks.search')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Task']);*/

    Route::get('{task}/complete', 'VentureDrake\LaravelCrm\Http\Controllers\TaskController@complete')
        ->name('laravel-crm.tasks.complete')
        ->middleware(['can:update,task']);
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
    Route::any('filter', 'VentureDrake\LaravelCrm\Http\Controllers\PersonController@index')
        ->name('laravel-crm.people.filter')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Person']);
    
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
    Route::any('filter', 'VentureDrake\LaravelCrm\Http\Controllers\OrganisationController@index')
        ->name('laravel-crm.organisations.filter')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Organisation']);
    
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

/* Product Categories */

Route::group(['prefix' => 'product-categories', 'middleware' => 'auth.laravel-crm'], function () {
    Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\ProductCategoryController@index')
        ->name('laravel-crm.product-categories.index')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\ProductCategory']);

    Route::get('create', 'VentureDrake\LaravelCrm\Http\Controllers\ProductCategoryController@create')
        ->name('laravel-crm.product-categories.create')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\ProductCategory']);

    Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\ProductCategoryController@store')
        ->name('laravel-crm.product-categories.store')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\ProductCategory']);

    Route::get('{productCategory}', 'VentureDrake\LaravelCrm\Http\Controllers\ProductCategoryController@show')
        ->name('laravel-crm.product-categories.show')
        ->middleware(['can:view,productCategory']);

    Route::get('{productCategory}/edit', 'VentureDrake\LaravelCrm\Http\Controllers\ProductCategoryController@edit')
        ->name('laravel-crm.product-categories.edit')
        ->middleware(['can:update,productCategory']);

    Route::put('{productCategory}', 'VentureDrake\LaravelCrm\Http\Controllers\ProductCategoryController@update')
        ->name('laravel-crm.product-categories.update')
        ->middleware(['can:update,productCategory']);

    Route::delete('{productCategory}', 'VentureDrake\LaravelCrm\Http\Controllers\ProductCategoryController@destroy')
        ->name('laravel-crm.product-categories.destroy')
        ->middleware(['can:delete,productCategory']);
});

/* Product Attributes */

Route::group(['prefix' => 'product-attributes', 'middleware' => 'auth.laravel-crm'], function () {
    Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\ProductAttributeController@index')
        ->name('laravel-crm.product-attributes.index')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\productAttribute']);

    Route::get('create', 'VentureDrake\LaravelCrm\Http\Controllers\ProductAttributeController@create')
        ->name('laravel-crm.product-attributes.create')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\productAttribute']);

    Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\ProductAttributeController@store')
        ->name('laravel-crm.product-attributes.store')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\productAttribute']);

    Route::get('{productCategory}', 'VentureDrake\LaravelCrm\Http\Controllers\ProductAttributeController@show')
        ->name('laravel-crm.product-attributes.show')
        ->middleware(['can:view,productAttribute']);

    Route::get('{productCategory}/edit', 'VentureDrake\LaravelCrm\Http\Controllers\ProductAttributeController@edit')
        ->name('laravel-crm.product-attributes.edit')
        ->middleware(['can:update,productAttribute']);

    Route::put('{productCategory}', 'VentureDrake\LaravelCrm\Http\Controllers\ProductAttributeController@update')
        ->name('laravel-crm.product-attributes.update')
        ->middleware(['can:update,productAttribute']);

    Route::delete('{productCategory}', 'VentureDrake\LaravelCrm\Http\Controllers\ProductAttributeController@destroy')
        ->name('laravel-crm.product-attributes.destroy')
        ->middleware(['can:delete,productAttribute']);
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

/* Labels */
Route::group(['prefix' => 'labels', 'middleware' => 'auth.laravel-crm'], function () {
    Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\LabelController@index')
        ->name('laravel-crm.labels.index')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Label']);

    Route::get('create', 'VentureDrake\LaravelCrm\Http\Controllers\LabelController@create')
        ->name('laravel-crm.labels.create')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\Label']);

    Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\LabelController@store')
        ->name('laravel-crm.labels.store')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\Label']);

    Route::get('{label}', 'VentureDrake\LaravelCrm\Http\Controllers\LabelController@show')
        ->name('laravel-crm.labels.show')
        ->middleware(['can:view,label']);

    Route::get('{label}/edit', 'VentureDrake\LaravelCrm\Http\Controllers\LabelController@edit')
        ->name('laravel-crm.labels.edit')
        ->middleware(['can:update,label']);

    Route::put('{label}', 'VentureDrake\LaravelCrm\Http\Controllers\LabelController@update')
        ->name('laravel-crm.labels.update')
        ->middleware(['can:update,label']);

    Route::delete('{label}', 'VentureDrake\LaravelCrm\Http\Controllers\LabelController@destroy')
        ->name('laravel-crm.labels.destroy')
        ->middleware(['can:delete,label']);
});

/* Fields */
Route::group(['prefix' => 'fields', 'middleware' => 'auth.laravel-crm'], function () {
    Route::get('', 'VentureDrake\LaravelCrm\Http\Controllers\FieldController@index')
        ->name('laravel-crm.fields.index')
        ->middleware(['can:viewAny,VentureDrake\LaravelCrm\Models\Field']);

    Route::get('create', 'VentureDrake\LaravelCrm\Http\Controllers\FieldController@create')
        ->name('laravel-crm.fields.create')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\Field']);

    Route::post('', 'VentureDrake\LaravelCrm\Http\Controllers\FieldController@store')
        ->name('laravel-crm.fields.store')
        ->middleware(['can:create,VentureDrake\LaravelCrm\Models\Field']);

    Route::get('{label}', 'VentureDrake\LaravelCrm\Http\Controllers\FieldController@show')
        ->name('laravel-crm.fields.show')
        ->middleware(['can:view,field']);

    Route::get('{label}/edit', 'VentureDrake\LaravelCrm\Http\Controllers\FieldController@edit')
        ->name('laravel-crm.fields.edit')
        ->middleware(['can:update,field']);

    Route::put('{label}', 'VentureDrake\LaravelCrm\Http\Controllers\FieldController@update')
        ->name('laravel-crm.fields.update')
        ->middleware(['can:update,field']);

    Route::delete('{label}', 'VentureDrake\LaravelCrm\Http\Controllers\FieldController@destroy')
        ->name('laravel-crm.fields.destroy')
        ->middleware(['can:delete,field']);
});

Route::group(['prefix' => 'integrations', 'middleware' => 'auth.laravel-crm'], function () {
    Route::group(['prefix' => 'xero'], function () {
        Route::get('', \VentureDrake\LaravelCrm\Http\Livewire\Integrations\Xero\XeroConnect::class)->name('laravel-crm.integrations.xero');

        Route::get('connect', function () {
            return \Dcblogdev\Xero\Facades\Xero::connect();
        })->name('laravel-crm.integrations.xero.connect');

        Route::get('disconnect', function () {
            if (\Dcblogdev\Xero\Facades\Xero::isConnected()) {
                \Dcblogdev\Xero\Facades\Xero::disconnect();
            }

            return redirect(route('laravel-crm.integrations.xero'));
        })->name('laravel-crm.integrations.xero.disconnect');
    });
});

Route::get('integrations', function () {
    return redirect(route('laravel-crm.integrations.xero'));
})->name('laravel-crm.integrations');

/* CRM (AJAX) */
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

/* Jetstream */
Route::put('/current-team', 'VentureDrake\LaravelCrm\Http\Controllers\Jetstream\CurrentTeamController@update')
    ->name('current-team.update')
    ->middleware(['auth', 'verified']);
