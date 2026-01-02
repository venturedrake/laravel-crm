<?php

namespace VentureDrake\LaravelCrm\Livewire\Products;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Products\Traits\HasProductCommon;

class ProductEdit extends Component
{
    use HasProductCommon;

    public function mount()
    {
        $this->currency = \VentureDrake\LaravelCrm\Models\Setting::currency()->value ?? 'USD';
        $this->user_owner_id = auth()->user()->id;
    }

    public function save()
    {
        $this->validate();

        // Create a request object to pass to services
        $request = \VentureDrake\LaravelCrm\Http\Helpers\PublicProperties\asRequest($this);

        /* $this->leadService->create($request, $person ?? null, $organization ?? null); */

        $this->success(
            ucfirst(trans('laravel-crm::lang.product_updated_successfully')),
            redirectTo: route('laravel-crm.products.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.products.product-edit');
    }
}
