<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\ProductCategories;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Settings\ProductCategories\Traits\HasProductCategoryCommon;
use VentureDrake\LaravelCrm\Models\ProductCategory;

class ProductCategoryEdit extends Component
{
    use HasProductCategoryCommon;

    public ProductCategory $productCategory;

    public function mount()
    {
        $this->name = $this->productCategory->name;
        $this->description = $this->productCategory->description;
    }

    public function save()
    {
        $this->validate();

        $this->productCategory->update([
            'name' => $this->name,
            'description' => $this->description,
        ]);

        $this->success(
            ucfirst(trans('laravel-crm::lang.product_category_updated')),
            redirectTo: route('laravel-crm.product-categories.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.product-categories.product-category-edit');
    }
}
