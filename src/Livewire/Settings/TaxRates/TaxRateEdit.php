<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\TaxRates;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Settings\TaxRates\Traits\HasTaxRateCommon;
use VentureDrake\LaravelCrm\Models\TaxRate;

class TaxRateEdit extends Component
{
    use HasTaxRateCommon;

    public ?TaxRate $taxRate = null;

    public function mount()
    {
        $this->name = $this->taxRate->name;
        $this->rate = $this->taxRate->rate;
        $this->description = $this->taxRate->description;
        $this->default = $this->taxRate->default;
        $this->tax_type = $this->taxRate->tax_type;
    }

    public function save()
    {
        $this->validate();

        $this->taxRate->update([
            'name' => $this->name,
            'rate' => $this->rate,
            'description' => $this->description,
            'default' => $this->default,
            'tax_type' => $this->tax_type,
        ]);

        $this->success(
            ucfirst(trans('laravel-crm::lang.tax_rate_updated')),
            redirectTo: route('laravel-crm.tax-rates.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.tax-rates.tax-rate-edit');
    }
}
