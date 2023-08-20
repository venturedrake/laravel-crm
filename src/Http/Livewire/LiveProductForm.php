<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Services\SettingService;

class LiveProductForm extends Component
{
    private $settingService;
    public $tax_rate_id;
    public $tax_rate;

    public function boot(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    public function mount($product)
    {
        $this->tax_rate_id = old('tax_rate_id') ?? $product->taxRate->id ?? null;
        $this->tax_rate = old('tax_rate') ??  $product->tax_rate ?? $product->taxRate->rate ?? null;
    }

    public function updatedTaxRateId($value)
    {
        if($value) {
            $this->tax_rate = TaxRate::find($value)->rate;
        } else {
            $this->tax_rate = number_format($this->settingService->get('tax_rate')->value ?? 0, 2);
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.product-form');
    }
}
