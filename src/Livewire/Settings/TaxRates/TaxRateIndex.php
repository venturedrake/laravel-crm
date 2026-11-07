<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\TaxRates;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Livewire\Traits\SortsTable;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Traits\ClearsProperties;
use VentureDrake\LaravelCrm\Traits\ResetsPaginationWhenPropsChanges;

class TaxRateIndex extends Component
{
    use AuthorizesRequests;
    use ClearsProperties, ResetsPaginationWhenPropsChanges, Toast, WithPagination;
    use SortsTable;

    public $layout = 'index';

    #[Url]
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public $dateFormat;

    public function mount()
    {
        $this->dateFormat = app('laravel-crm.settings')->get('date_format', config('laravel-crm.date_format'));
    }

    public function headers()
    {
        return [
            ['key' => 'name', 'label' => ucfirst(__('laravel-crm::lang.name'))],
            ['key' => 'rate', 'label' => ucfirst(__('laravel-crm::lang.rate'))],
            ['key' => 'default', 'label' => ucfirst(__('laravel-crm::lang.default')), 'format' => fn ($row, $field) => $field ? 'YES' : 'NO'],
            ['key' => 'tax_type', 'label' => ucfirst(__('laravel-crm::lang.tax_type'))],
            ['key' => 'products', 'label' => ucfirst(__('laravel-crm::lang.products')), 'format' => fn ($row, $field) => count($field), 'sortable' => false],
            ['key' => 'created_at', 'label' => ucfirst(__('laravel-crm::lang.created')), 'format' => fn ($row, $field) => $field->format($this->dateFormat)],
            ['key' => 'updated_at', 'label' => ucfirst(__('laravel-crm::lang.updated')), 'format' => fn ($row, $field) => $field->format($this->dateFormat)],
        ];
    }

    public function taxRates(): LengthAwarePaginator
    {
        return TaxRate::orderBy(...$this->tableSort())
            ->paginate(25);
    }

    public function delete($id)
    {
        if ($taxRate = TaxRate::find($id)) {
            $this->authorize('delete', $taxRate);

            $taxRate->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.tax_rate_deleted')));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.tax-rates.tax-rate-index', [
            'headers' => $this->headers(),
            'taxRates' => $this->taxRates(),
        ]);
    }
}
