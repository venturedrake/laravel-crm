<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\TaxRates;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\TaxRate;

class TaxRateShow extends Component
{
    use AuthorizesRequests;
    use Toast;

    public TaxRate $taxRate;

    public function delete($id)
    {
        if ($taxRate = TaxRate::find($id)) {
            $this->authorize('delete', $taxRate);

            if (($count = $taxRate->productsCount()) > 0) {
                $this->error(ucfirst(__('laravel-crm::lang.cannot_delete_tax_rate_in_use', ['count' => $count])));

                return;
            }

            $taxRate->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.tax_rate_deleted')), redirectTo: route('laravel-crm.tax-rates.index'));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.tax-rates.tax-rate-show');
    }
}
