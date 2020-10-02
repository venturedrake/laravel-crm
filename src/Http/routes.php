<?php

Route::get('crm', function(){
    return response('crm route reserved for Laravel CRM', 200)
        ->header('Content-Type', 'text/plain');
})->name('laravelcrm.index');