<?php
 
namespace VentureDrake\LaravelCrm\Http\Livewire\Integrations\Xero;

use Dcblogdev\Xero\Facades\Xero;
use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class XeroConnect extends Component
{
    use NotifyToast;
    
    public $tennantName;

    public $invoices;

    public $contacts;
    
    public $setting_contacts = false;

    public $setting_products = false;

    public $setting_quotes = false;

    public $setting_invoices = false;

    /**
     * @inheritDoc
     *
     * @return void
     */
    public function mount()
    {
        if (Xero::isConnected()) {
            $this->tenantName = Xero::getTenantName();
            $this->invoices = Xero::invoices()->get();
            $this->contacts = Xero::contacts()->get();
        }
        
        $this->updateSettings();
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

    public function render()
    {
        return view('laravel-crm::livewire.integrations.xero.xero-connect')
            ->layout('laravel-crm::layouts.app');
    }
}
