<?php

use Illuminate\Support\Facades\Facade;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Settings\Integrations\Xero\XeroConnect;
use VentureDrake\LaravelCrm\Models\Setting;

/*
 * Issue #145: the Xero integrations page threw `ReferenceError: $ is not
 * defined` on every visit, because the view pushed a v1 jQuery script into the
 * `livewire-js` stack and the v2 layout doesn't load jQuery. The MaryUI toggles
 * are bound with `wire:model`, so the page needs no JS of its own.
 *
 * Fixing that exposed two bugs in the connected branch: the tenant name was
 * written to an undeclared `$tenantName` (the declared property was misspelt
 * `$tennantName`), so a connected install still showed "Connect to Xero"; and
 * saving called a `notify()` method the Mary `Toast` trait doesn't have.
 */

beforeEach(function () {
    $this->actingAsUserWithPermissions([
        'view crm settings',
        'edit crm settings',
    ]);
});

function connectedXeroForSettings(string $tenantName = 'Acme Ltd'): void
{
    $xero = new class($tenantName)
    {
        public function __construct(private string $tenantName) {}

        public function isConnected(): bool
        {
            return true;
        }

        public function getTenantName(): string
        {
            return $this->tenantName;
        }

        public function __call($name, $args)
        {
            return null;
        }
    };

    app()->instance('xero', $xero);
    Facade::clearResolvedInstance('xero');
}

it('renders the integrations page without any jQuery', function () {
    $response = $this->get(route('laravel-crm.integrations.xero'));

    $response->assertOk()
        ->assertSee('Connect to Xero')
        ->assertDontSee('$(document)', false)
        ->assertDontSee('jQuery', false);
});

it('renders the not-installed notice without any jQuery', function () {
    Livewire::test(XeroConnect::class)
        ->set('installed', false)
        ->assertSee('composer require dcblogdev/laravel-xero')
        ->assertDontSee('$(document)', false)
        ->assertDontSee('jQuery', false);
});

it('shows the connected organization and pre-fills the settings toggles', function () {
    connectedXeroForSettings();

    Setting::create(['name' => 'xero_contacts', 'value' => 1]);
    Setting::create(['name' => 'xero_products', 'value' => 0]);
    Setting::create(['name' => 'xero_invoices', 'value' => 1]);

    Livewire::test(XeroConnect::class)
        ->assertSet('tenantName', 'Acme Ltd')
        ->assertSee('You are connected to the Xero organization')
        ->assertSee('Acme Ltd')
        ->assertDontSee('Connect to Xero')
        ->assertSet('setting_contacts', true)
        ->assertSet('setting_products', false)
        ->assertSet('setting_invoices', true);
});

it('renders the connected page over HTTP without any jQuery', function () {
    connectedXeroForSettings();

    $this->get(route('laravel-crm.integrations.xero'))
        ->assertOk()
        ->assertSee('Acme Ltd')
        ->assertDontSee('$(document)', false)
        ->assertDontSee('jQuery', false);
});

it('saves the settings toggles', function () {
    connectedXeroForSettings();

    Livewire::test(XeroConnect::class)
        ->set('setting_contacts', true)
        ->set('setting_products', true)
        ->set('setting_invoices', false)
        ->call('updateSettings')
        ->assertHasNoErrors();

    expect(Setting::where('name', 'xero_contacts')->value('value'))->toBe('1')
        ->and(Setting::where('name', 'xero_products')->value('value'))->toBe('1')
        ->and(Setting::where('name', 'xero_invoices')->value('value'))->toBe('0');
});

it('still shows the ClickSend tab alongside Xero', function () {
    $this->get(route('laravel-crm.integrations.xero'))
        ->assertOk()
        ->assertSee('ClickSend')
        ->assertSee(route('laravel-crm.integrations.clicksend'), false);
});
