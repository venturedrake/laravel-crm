<?php

namespace VentureDrake\LaravelCrm\Livewire\Products;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Product;

class ProductShow extends Component
{
    use AuthorizesRequests, Toast;

    public Product $product;

    public function mount()
    {
        $this->product->loadMissing('productPrices', 'productVariations.productPrices');
    }

    public function delete($id)
    {
        if ($product = Product::find($id)) {
            $this->authorize('delete', $product);

            $product->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.product_deleted')), redirectTo: route('laravel-crm.products.index'));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.products.product-show');
    }
}
