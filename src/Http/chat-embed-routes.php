<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Chat Widget Embed Routes (PUBLIC, no web middleware)
|--------------------------------------------------------------------------
|
| These routes are intentionally registered OUTSIDE the `web` middleware
| group so that they bypass session/CSRF — the visitor is authenticated
| by an opaque `visitor_token` stored in the iframe's localStorage.
|
| This is what makes the chat widget embeddable on any third-party site
| without "419 Page Expired" errors caused by cross-origin cookie blocks.
|
*/

Route::group(['prefix' => 'p/chat'], function () {

    // Loader script: <script src="/p/chat/{key}.js"></script>
    Route::get('{publicKey}.js', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\ChatWidgetEmbedController@script')
        ->name('laravel-crm.portal.chat.embed');

    // Iframe HTML page
    Route::get('{publicKey}', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\ChatWidgetEmbedController@widget')
        ->name('laravel-crm.portal.chat.widget');

    // JSON API — token-authenticated
    Route::match(['post', 'options'], '{publicKey}/init', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\ChatWidgetEmbedController@init')
        ->name('laravel-crm.portal.chat.init');

    Route::get('{publicKey}/messages', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\ChatWidgetEmbedController@messages')
        ->name('laravel-crm.portal.chat.messages');

    Route::match(['post', 'options'], '{publicKey}/messages/send', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\ChatWidgetEmbedController@send')
        ->name('laravel-crm.portal.chat.send');

    Route::match(['post', 'options'], '{publicKey}/identify', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\ChatWidgetEmbedController@identify')
        ->name('laravel-crm.portal.chat.identify');

    Route::match(['post', 'options'], '{publicKey}/markread', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\ChatWidgetEmbedController@markRead')
        ->name('laravel-crm.portal.chat.markread');

    Route::match(['post', 'options'], '{publicKey}/track', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\ChatWidgetEmbedController@track')
        ->name('laravel-crm.portal.chat.track');
});
