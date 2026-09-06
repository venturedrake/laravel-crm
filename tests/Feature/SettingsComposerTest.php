<?php

use VentureDrake\LaravelCrm\View\Composers\SettingsComposer;

/**
 * Compose a view and hand back the variables the composer shared with it.
 */
function composedSettings(): array
{
    $view = view('laravel-crm::leads.index');

    (new SettingsComposer)->compose($view);

    return $view->getData();
}

test('default values are used when settings missing', function () {
    $data = composedSettings();

    expect($data['crmDateFormat'])->toBe('Y-m-d');
    expect($data['crmTimeFormat'])->toBe('H:i');
    expect($data['crmTimezone'])->toBe('UTC');
    expect($data['crmTaxName'])->toBe('Tax');
    expect($data['crmDynamicProducts'])->toBe('true');
});

test('settings table values override defaults', function () {
    app('laravel-crm.settings')->set('date_format', 'd/m/Y');
    app('laravel-crm.settings')->set('time_format', 'g:i A');
    app('laravel-crm.settings')->set('tax_name', 'GST');

    $data = composedSettings();

    expect($data['crmDateFormat'])->toBe('d/m/Y');
    expect($data['crmTimeFormat'])->toBe('g:i A');
    expect($data['crmTaxName'])->toBe('GST');
});

test('dynamic products is normalised to a string boolean', function () {
    app('laravel-crm.settings')->set('dynamic_products', 0);

    expect(composedSettings()['crmDynamicProducts'])->toBe('false');

    app('laravel-crm.settings')->set('dynamic_products', 1);

    expect(composedSettings()['crmDynamicProducts'])->toBe('true');
});

test('a settings change is visible on the very next render', function () {
    // The composer used to keep its own hour-long cache with no invalidation
    // path, so a date_format change took up to an hour to reach any view.
    app('laravel-crm.settings')->set('date_format', 'd/m/Y');

    expect(composedSettings()['crmDateFormat'])->toBe('d/m/Y');

    app('laravel-crm.settings')->set('date_format', 'm/d/Y');

    expect(composedSettings()['crmDateFormat'])->toBe('m/d/Y');
});
