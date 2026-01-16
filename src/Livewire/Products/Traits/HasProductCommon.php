<?php

namespace VentureDrake\LaravelCrm\Livewire\Products\Traits;

use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Services\ProductService;

trait HasProductCommon
{
    use Toast;

    protected ProductService $productService;

    public $name;

    public $code;

    public $barcode;

    public array $productCategories = [
        ['id' => null, 'name' => null],
    ];

    public $product_category;

    public $purchase_account;

    public $sales_account;

    public $description;

    public $unit;

    public $unit_price;

    public array $taxRates = [
        ['id' => null, 'name' => null],
    ];

    public $tax_rate_id;

    public $tax_rate;

    public $currency;

    public $user_owner_id;

    protected function rules()
    {
        return [
            'name' => 'required|max:255',
        ];
    }

    public function boot(ProductService $productService): void
    {
        $this->productService = $productService;
    }

    public function mountCommon()
    {
        foreach (\VentureDrake\LaravelCrm\Models\ProductCategory::all() as $productCategory) {
            $this->productCategories[] = [
                'id' => $productCategory->id,
                'name' => $productCategory->name,
            ];
        }

        foreach (\VentureDrake\LaravelCrm\Models\TaxRate::get() as $taxRate) {
            $this->taxRates[] = [
                'id' => $taxRate->id,
                'name' => $taxRate->name,
                'rate' => $taxRate->rate,
            ];
        }
    }

    public function updatedTaxRateId($value)
    {
        if ($value) {
            $this->tax_rate = TaxRate::find($value)->rate;
        } else {
            $this->tax_rate = number_format(app('laravel-crm.settings')->get('tax_rate', 0), 2);
        }
    }
}
