<?php

namespace VentureDrake\LaravelCrm\Livewire\Products;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Product;

class ProductShow extends Component
{
    public Product $product;

    public function render()
    {
        return view('laravel-crm::livewire.products.product-show');
    }
}
