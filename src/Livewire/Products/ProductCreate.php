<?php

namespace VentureDrake\LaravelCrm\Livewire\Products;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Products\Traits\HasProductCommon;

class ProductCreate extends Component
{
    use HasProductCommon;

    public function mount()
    {
        $this->mountCommon();
    }

    public function save()
    {
        $this->validate();

        // Create a request object to pass to services
        $request = \VentureDrake\LaravelCrm\Http\Helpers\PublicProperties\asRequest($this);

        /* $this->leadService->create($request, $person ?? null, $organization ?? null); */

        $this->success(
            ucfirst(trans('laravel-crm::lang.product_created_successfully')),
            redirectTo: route('laravel-crm.products.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.products.product-create');
    }
}
