<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\TaxRates;

use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\TaxRate;

class TaxRateShow extends Component
{
    use Toast;

    public TaxRate $taxRate;

    public function delete($id)
    {
        if ($taxRate = TaxRate::find($id)) {
            $taxRate->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.tax_rate_deleted')), redirectTo: route('laravel-crm.tax-rates.index'));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.tax-rates.tax-rate-show');
    }
}
