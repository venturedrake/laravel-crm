<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\ProductCategories;

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\ProductCategory;
use VentureDrake\LaravelCrm\Traits\ClearsProperties;
use VentureDrake\LaravelCrm\Traits\ResetsPaginationWhenPropsChanges;

class ProductCategoryIndex extends Component
{
    use ClearsProperties, ResetsPaginationWhenPropsChanges, Toast, WithPagination;

    public $layout = 'index';

    #[Url]
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public function mount()
    {
        $this->dateFormat = app('laravel-crm.settings')->get('date_format', config('laravel-crm.date_format'));
    }

    public function headers()
    {
        return [
            ['key' => 'name', 'label' => ucfirst(__('laravel-crm::lang.name'))],
            ['key' => 'products', 'label' => ucfirst(__('laravel-crm::lang.products')), 'format' => fn ($row, $field) => count($field)],
            ['key' => 'created_at', 'label' => ucfirst(__('laravel-crm::lang.created')), 'format' => fn ($row, $field) => $field->format($this->dateFormat)],
            ['key' => 'updated_at', 'label' => ucfirst(__('laravel-crm::lang.updated')), 'format' => fn ($row, $field) => $field->format($this->dateFormat)],
        ];
    }

    public function productCategories(): LengthAwarePaginator
    {
        return ProductCategory::orderBy(...array_values($this->sortBy))
            ->paginate(25);
    }

    public function delete($id)
    {
        if ($productCategory = ProductCategory::find($id)) {
            $productCategory->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.product_category_deleted')));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.product-categories.product-category-index', [
            'headers' => $this->headers(),
            'productCategories' => $this->productCategories(),
        ]);
    }
}
