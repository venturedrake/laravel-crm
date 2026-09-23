<?php

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Settings\Integrations\Xero\XeroConnect;

/*
 * The Xero settings screen still exists when `dcblogdev/laravel-xero` isn't
 * installed — it has to, because the integrations tab strip and the app layout
 * both build links to `laravel-crm.integrations.xero` by name, so hiding the
 * route would throw a RouteNotFoundException on every page that renders the
 * settings menu. What the screen must not do is offer a "Connect to Xero"
 * button that dead-ends in the connect route.
 *
 * The suite installs the package as a dev dependency, so `$installed` is true
 * here and the absent branch is reached by setting the property directly.
 */

beforeEach(function () {
    $this->actingAsUserWithPermissions([
        'view crm settings',
        'edit crm settings',
    ]);
});

it('tells the admin how to install Xero instead of offering a dead connect button', function () {
    Livewire::test(XeroConnect::class)
        ->set('installed', false)
        ->assertSee('composer require dcblogdev/laravel-xero')
        ->assertDontSee('Connect to Xero');
});

it('offers the connect button once the package is installed', function () {
    Livewire::test(XeroConnect::class)
        ->set('installed', true)
        ->assertSee('Connect to Xero')
        ->assertDontSee('composer require dcblogdev/laravel-xero');
});
