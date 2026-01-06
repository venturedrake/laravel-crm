<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\ProductCategories;

use Livewire\Component;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Livewire\Settings\ProductCategories\Traits\HasProductCategoryCommon;
use VentureDrake\LaravelCrm\Models\ProductCategory;

class ProductCategoryCreate extends Component
{
    use HasProductCategoryCommon;

    public function save()
    {
        $this->validate();

        ProductCategory::create([
            'external_id' => Uuid::uuid4()->toString(),
            'name' => $this->name,
            'description' => $this->description,
        ]);

        $this->success(
            ucfirst(trans('laravel-crm::lang.product_category_created')),
            redirectTo: route('laravel-crm.product-categories.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.product-categories.product-category-create');
    }
}
