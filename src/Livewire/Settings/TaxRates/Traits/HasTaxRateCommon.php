<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\TaxRates\Traits;

use Mary\Traits\Toast;

trait HasTaxRateCommon
{
    use Toast;

    public $name;

    public $rate;

    public $description;

    public bool $default = false;

    public $tax_type;

    protected function rules()
    {
        return [
            'name' => 'required|max:255',
            'rate' => 'required',
        ];
    }
}
