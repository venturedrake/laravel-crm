<?php

namespace VentureDrake\LaravelCrm\Livewire\Products;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Products\Traits\HasProductCommon;
use VentureDrake\LaravelCrm\Models\TaxRate;

class ProductCreate extends Component
{
    use HasProductCommon;

    public function mount()
    {
        $this->mountCommon();

        if ($taxRate = TaxRate::where('default', 1)->first()) {
            $this->tax_rate_id = $taxRate->id;
            $this->tax_rate = $taxRate->rate;
        }

        $this->currency = app('laravel-crm.settings')->get('currency', 'USD');
        $this->user_owner_id = auth()->user()->id;
    }

    public function save()
    {
        $this->validate();

        // Create a request object to pass to services
        $request = \VentureDrake\LaravelCrm\Http\Helpers\PublicProperties\asRequest($this);

        $product = $this->productService->create($request);

        $this->success(
            ucfirst(trans('laravel-crm::lang.product_created')),
            redirectTo: route('laravel-crm.products.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.products.product-create');
    }
}
