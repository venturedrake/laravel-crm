<?php

namespace VentureDrake\LaravelCrm\Livewire\Products;

use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Product;

class ProductShow extends Component
{
    use Toast;
    
    public Product $product;

    public function delete($id)
    {
        if ($product = Product::find($id)) {
            $product->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.product_deleted')), redirectTo: route('laravel-crm.products.index'));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.products.product-show');
    }
}
