<?php

namespace VentureDrake\LaravelCrm\Livewire\Products;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Traits\ClearsProperties;
use VentureDrake\LaravelCrm\Traits\ResetsPaginationWhenPropsChanges;

class ProductIndex extends Component
{
    use ClearsProperties, ResetsPaginationWhenPropsChanges, Toast, WithPagination;

    public $layout = 'index';

    #[Url]
    public string $search = '';

    #[Url]
    public ?array $user_id = [];

    #[Url]
    public ?array $label_id = [];

    #[Url]
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public bool $showFilters = false;

    public $dateFormat;

    public $currency;

    public function mount()
    {
        $this->dateFormat = app('laravel-crm.settings')->get('date_format', config('laravel-crm.date_format'));
        $this->currency = app('laravel-crm.settings')->get('currency', config('laravel-crm.currency'));
    }

    public function filterCount(): int
    {
        return (count($this->user_id) > 0 ? 1 : 0) + ($this->label_id ? 1 : 0);
    }

    public function users(): Collection
    {
        return User::orderBy('name')->get();
    }

    public function labels(): Collection
    {
        return Label::all();
    }

    public function headers()
    {
        return [
            ['key' => 'name', 'label' => ucfirst(__('laravel-crm::lang.name'))],
            ['key' => 'xeroItem', 'label' => '', 'sortable' => false],
            ['key' => 'code', 'label' => strtoupper(__('laravel-crm::lang.sku'))],
            ['key' => 'productCategory.name', 'label' => ucfirst(__('laravel-crm::lang.category'))],
            ['key' => 'unit', 'label' => ucfirst(__('laravel-crm::lang.unit'))],
            ['key' => 'price', 'label' => ucfirst(__('laravel-crm::lang.price')).' ('.$this->currency.')'],
            ['key' => 'taxRate.name', 'label' => ucfirst(__('laravel-crm::lang.tax'))],
            ['key' => 'taxRate.rate', 'label' => ucfirst(__('laravel-crm::lang.tax_rate')), 'format' => fn ($row, $field) => $field.'%'],
            ['key' => 'active', 'label' => ucfirst(__('laravel-crm::lang.active')), 'format' => fn ($row, $field) => $field ? ucfirst(__('laravel-crm::lang.yes')) : ucfirst(__('laravel-crm::lang.no'))],
            ['key' => 'ownerUser.name', 'label' => 'Owner', 'format' => fn ($row, $field) => $field ?? ucfirst(__('laravel-crm::lang.unallocated')), 'sortable' => false],
            ['key' => 'created_at', 'label' => ucfirst(__('laravel-crm::lang.created')), 'format' => fn ($row, $field) => $field->diffForHumans()],
        ];
    }

    public function products(): LengthAwarePaginator
    {
        return Product::when($this->search, function (Builder $q) {
            $q->where('name', 'like', "%$this->search%");
        })->when($this->user_id, fn (Builder $q) => $q->whereIn('user_owner_id', $this->user_id))
            ->when($this->label_id, fn (Builder $q) => $q->whereHas('labels', fn (Builder $q) => $q->whereIn('labels.id', $this->label_id)))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(25);
    }

    public function delete($id)
    {
        if ($product = Product::find($id)) {
            $product->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.product_deleted')));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.products.product-index', [
            'users' => $this->users(),
            'labels' => $this->labels(),
            'filterCount' => $this->filterCount(),
            'headers' => $this->headers(),
            'products' => $this->products(),
        ]);
    }
}
