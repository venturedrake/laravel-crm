<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\ProductCategories;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Settings\ProductCategories\Traits\HasProductCategoryCommon;
use VentureDrake\LaravelCrm\Models\ProductCategory;

class ProductCategoryEdit extends Component
{
    use AuthorizesRequests;
    use HasProductCategoryCommon;

    public ProductCategory $productCategory;

    public function mount()
    {
        $this->name = $this->productCategory->name;
        $this->description = $this->productCategory->description;
    }

    public function save()
    {
        $this->authorize('update', $this->productCategory);

        $this->validate();

        $this->productCategory->update([
            'name' => $this->name,
            'description' => $this->description,
        ]);

        $this->success(
            ucfirst(trans('laravel-crm::lang.product_category_updated')),
            redirectTo: route('laravel-crm.product-categories.show', $this->productCategory)
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.product-categories.product-category-edit');
    }
}
