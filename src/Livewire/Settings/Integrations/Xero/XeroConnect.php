<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\Integrations\Xero;

use Dcblogdev\Xero\Facades\Xero;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Setting;

class XeroConnect extends Component
{
    use Toast;

    public $tennantName;

    public $invoices;

    public $contacts;

    public $setting_contacts;

    public $setting_products;

    public $setting_quotes;

    public $setting_invoices;

    /**
     * {@inheritDoc}
     *
     * @return void
     */
    public function mount()
    {
        if (Xero::isConnected()) {
            $this->tenantName = Xero::getTenantName();
            /*$this->invoices = Xero::invoices()->get();
            $this->contacts = Xero::contacts()->get();*/

            $this->setting_contacts = $this->trueFalse(Setting::where('name', 'xero_contacts')->first()->value ?? 0);
            $this->setting_products = $this->trueFalse(Setting::where('name', 'xero_products')->first()->value ?? 0);
            $this->setting_quotes = $this->trueFalse(Setting::where('name', 'xero_quotes')->first()->value ?? 0);
            $this->setting_invoices = $this->trueFalse(Setting::where('name', 'xero_invoices')->first()->value ?? 0);
        }
    }

    public function updateSettings()
    {
        Setting::updateOrCreate([
            'name' => 'xero_contacts',
        ], [
            'value' => $this->setting_contacts,
        ]);

        Setting::updateOrCreate([
            'name' => 'xero_products',
        ], [
            'value' => $this->setting_products,
        ]);

        Setting::updateOrCreate([
            'name' => 'xero_quotes',
        ], [
            'value' => $this->setting_quotes,
        ]);

        Setting::updateOrCreate([
            'name' => 'xero_invoices',
        ], [
            'value' => $this->setting_invoices,
        ]);

        $this->notify(
            'Updated settings',
        );
    }

    protected function trueFalse($value)
    {
        if ($value == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.integrations.xero.xero-connect')
            ->layout('laravel-crm::layouts.app');
    }
}
