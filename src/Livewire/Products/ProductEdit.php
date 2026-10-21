<?php

namespace VentureDrake\LaravelCrm\Livewire\Products;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Products\Traits\HasProductCommon;
use VentureDrake\LaravelCrm\Models\Product;

class ProductEdit extends Component
{
    use AuthorizesRequests, HasProductCommon;

    public Product $product;

    public $layout = 'full';

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
        // The row update() saves to. For a product priced only in another
        // currency, the default price would load blanks that saving clears.
        $productPrice = $this->product->getPrimaryPrice();

        $this->unit_price = isset($productPrice->unit_price) ? ($productPrice->unit_price / 100) : null;
        $this->tax_rate_id = $this->product->tax_rate_id;
        $this->tax_rate = $this->product->tax_rate;
        $this->currency = $productPrice->currency ?? app('laravel-crm.settings')->get('currency', 'USD');
        $this->user_owner_id = $this->product->user_owner_id;
        $this->variations = $this->variationRows($this->product, $this->currency);

        $this->loadCustomFields($this->product);
    }

    public function save()
    {
        $this->authorize('update', $this->product);

        $this->validate();

        // Create a request object to pass to services
        $request = \VentureDrake\LaravelCrm\Http\Helpers\PublicProperties\asRequest($this);

        $this->productService->update($this->product, $request);

        $this->saveCustomFields($this->product->fresh());

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
