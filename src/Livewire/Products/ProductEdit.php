<?php

namespace VentureDrake\LaravelCrm\Livewire\Products;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Products\Traits\HasProductCommon;
use VentureDrake\LaravelCrm\Models\Product;

class ProductEdit extends Component
{
    use HasProductCommon;

    public Product $product;

    public function mount()
    {
        $this->mountCommon();

        $this->name = $this->product->name;
        $this->code = $this->product->code;
        $this->barcode = $this->product->barcode;
        $this->product_category = $this->product->product_category_id;
        $this->purchase_account = $this->product->purchase_account;
        $this->sales_account = $this->product->sales_account;
        $this->description = $this->product->description;
        $this->unit = $this->product->unit;
        $this->unit_price = (isset($this->product->getDefaultPrice()->unit_price)) ? ($this->product->getDefaultPrice()->unit_price / 100) : null;
        $this->tax_rate_id = $this->product->tax_rate_id;
        $this->tax_rate = $this->product->tax_rate;
        $this->currency = (isset($this->product->getDefaultPrice()->currency)) ? $this->product->getDefaultPrice()->currency : app('laravel-crm.settings')->get('currency', 'USD');
        $this->user_owner_id = $this->product->user_owner_id;
    }

    public function save()
    {
        $this->validate();

        // Create a request object to pass to services
        $request = \VentureDrake\LaravelCrm\Http\Helpers\PublicProperties\asRequest($this);

        $this->productService->update($this->product, $request);

        $this->success(
            ucfirst(trans('laravel-crm::lang.product_updated')),
            redirectTo: route('laravel-crm.products.show', $this->product)
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.products.product-edit');
    }
}
