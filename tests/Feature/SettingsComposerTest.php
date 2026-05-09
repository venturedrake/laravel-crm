<?php

use VentureDrake\LaravelCrm\View\Composers\SettingsComposer;

test('default values are used when settings missing', function () {
    SettingsComposer::$cachedParameters = null;
    $composer = new SettingsComposer;
    $composer->compose(view('laravel-crm::leads.index'));

    expect(SettingsComposer::$cachedParameters['crmDateFormat'])->toBe('Y-m-d');
    expect(SettingsComposer::$cachedParameters['crmTimeFormat'])->toBe('H:i');
    expect(SettingsComposer::$cachedParameters['crmTimezone'])->toBe('UTC');
    expect(SettingsComposer::$cachedParameters['crmTaxName'])->toBe('Tax');
    expect(SettingsComposer::$cachedParameters['crmDynamicProducts'])->toBe('true');
});

test('settings table values override defaults', function () {
    app('laravel-crm.settings')->set('date_format', 'd/m/Y');
    app('laravel-crm.settings')->set('time_format', 'g:i A');
    app('laravel-crm.settings')->set('tax_name', 'GST');

    SettingsComposer::$cachedParameters = null;
    $composer = new SettingsComposer;
    $composer->compose(view('laravel-crm::leads.index'));

    expect(SettingsComposer::$cachedParameters['crmDateFormat'])->toBe('d/m/Y');
    expect(SettingsComposer::$cachedParameters['crmTimeFormat'])->toBe('g:i A');
    expect(SettingsComposer::$cachedParameters['crmTaxName'])->toBe('GST');
});
