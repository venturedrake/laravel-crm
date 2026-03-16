<?php

namespace VentureDrake\LaravelCrm\Livewire\Orders;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Orders\Traits\HasOrderCommon;
use VentureDrake\LaravelCrm\Livewire\Traits\HasOrganizationSuggest;
use VentureDrake\LaravelCrm\Livewire\Traits\HasPersonSuggest;
use VentureDrake\LaravelCrm\Models\AddressType;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Setting;

class OrderCreate extends Component
{
    use HasOrderCommon;
    use HasOrganizationSuggest;
    use HasPersonSuggest;

    protected $listeners = [
        'model-products-updated' => 'updateProducts',
    ];

    public function mount()
    {
        $this->mountCommon();

        $this->currency = Setting::currency()->value ?? 'USD';
        $this->pipeline_stage_id = $this->pipeline->pipelineStages->first()->id ?? null;
        $this->user_owner_id = auth()->user()->id;

        $this->addresses['billing']['address_type_id'] = AddressType::where('name', 'Billing')->first()->id ?? null;
        $this->addresses['shipping']['address_type_id'] = AddressType::where('name', 'Shipping')->first()->id ?? null;
        $this->addresses['billing']['country'] = app('laravel-crm.settings')->get('country', 'United States');
        $this->addresses['shipping']['country'] = app('laravel-crm.settings')->get('country', 'United States');
    }

    public function save()
    {
        $this->validate();

        // Create a request object to pass to services
        $request = \VentureDrake\LaravelCrm\Http\Helpers\PublicProperties\asRequest($this);

        if ($this->person_name && ! $this->person_id) {
            $person = $this->personService->createFromRelated($request);
        } elseif ($this->person_id) {
            $person = Person::find($this->person_id);
        }

        if ($this->organization_name && ! $this->organization_id) {
            $organization = $this->organizationService->createFromRelated($request);
        } elseif ($this->organization_id) {
            $organization = Organization::find($this->organization_id);
        }

        $this->orderService->create($request, $person ?? null, $organization ?? null);

        $this->success(
            ucfirst(trans('laravel-crm::lang.order_created')),
            redirectTo: route('laravel-crm.orders.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.orders.order-create');
    }
}
