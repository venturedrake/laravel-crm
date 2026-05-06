<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Email Campaign Tracking Routes (PUBLIC, no web middleware)
|--------------------------------------------------------------------------
|
| Open pixel, click tracker and unsubscribe routes are registered outside
| the `web` middleware group so tracking pixels and one-click links work
| from any email client without CSRF/session interference.
|
*/

Route::group(['prefix' => 'p/email'], function () {

    Route::get('o/{token}.gif', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\EmailCampaignTrackingController@open')
        ->name('laravel-crm.email-tracking.open');

    Route::get('c/{token}', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\EmailCampaignTrackingController@click')
        ->name('laravel-crm.email-tracking.click');

    Route::get('u/{token}', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\EmailCampaignTrackingController@unsubscribeForm')
        ->name('laravel-crm.email-tracking.unsubscribe');

    Route::post('u/{token}', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\EmailCampaignTrackingController@unsubscribe')
        ->name('laravel-crm.email-tracking.unsubscribe.confirm');
});

Route::group(['prefix' => 'p/sms'], function () {

    Route::get('c/{token}', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\SmsCampaignTrackingController@click')
        ->middleware('throttle:60,1')
        ->name('laravel-crm.sms-tracking.click');

    Route::get('u/{token}', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\SmsCampaignTrackingController@unsubscribeForm')
        ->middleware('throttle:30,1')
        ->name('laravel-crm.sms-tracking.unsubscribe');

    Route::post('u/{token}', 'VentureDrake\LaravelCrm\Http\Controllers\Portal\SmsCampaignTrackingController@unsubscribe')
        ->middleware('throttle:30,1')
        ->name('laravel-crm.sms-tracking.unsubscribe.confirm');
});
