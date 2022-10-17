<?php
 
namespace VentureDrake\LaravelCrm\Http\Livewire\Integrations\Xero;

use Dcblogdev\Xero\Facades\Xero;
use Livewire\Component;

class XeroConnect extends Component
{
    public $tennantName;

    public $invoices;

    public $contacts;

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
    }

    public function render()
    {
        return view('laravel-crm::livewire.integrations.xero.xero-connect')
            ->layout('laravel-crm::layouts.app');
    }
}
