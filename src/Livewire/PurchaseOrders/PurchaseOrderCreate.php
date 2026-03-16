<?php

namespace VentureDrake\LaravelCrm\Livewire\PurchaseOrders;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\PurchaseOrders\Traits\HasPurchaseOrderCommon;
use VentureDrake\LaravelCrm\Livewire\Traits\HasOrganizationSuggest;
use VentureDrake\LaravelCrm\Livewire\Traits\HasPersonSuggest;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Setting;

class PurchaseOrderCreate extends Component
{
    use HasOrganizationSuggest;
    use HasPersonSuggest;
    use HasPurchaseOrderCommon;

    protected $listeners = [
        'model-products-updated' => 'updateProducts',
    ];

    public function mount()
    {
        $this->mountCommon();

        $this->currency = Setting::currency()->value ?? 'USD';
        $this->pipeline_stage_id = $this->pipeline->pipelineStages->first()->id ?? null;
        $this->user_owner_id = auth()->user()->id;
        $this->terms = app('laravel-crm.settings')->get('purchase_order_terms');
        $this->delivery_instructions = app('laravel-crm.settings')->get('purchase_order_delivery_instructions');
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

        $this->purchaseOrderService->create($request, $person ?? null, $organization ?? null);

        $this->success(
            ucfirst(trans('laravel-crm::lang.purchase_order_created')),
            redirectTo: route('laravel-crm.purchase-orders.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.purchase-orders.purchase-order-create');
    }
}
