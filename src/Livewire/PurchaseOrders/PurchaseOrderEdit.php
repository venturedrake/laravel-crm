<?php

namespace VentureDrake\LaravelCrm\Livewire\PurchaseOrders;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\PurchaseOrders\Traits\HasPurchaseOrderCommon;
use VentureDrake\LaravelCrm\Livewire\Traits\HasOrganizationSuggest;
use VentureDrake\LaravelCrm\Livewire\Traits\HasPersonSuggest;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;

class PurchaseOrderEdit extends Component
{
    use HasPurchaseOrderCommon;
    use HasOrganizationSuggest;
    use HasPersonSuggest;
    
    public PurchaseOrder $purchaseOrder;

    protected $listeners = [
        'model-products-updated' => 'updateProducts',
    ];
   

    public function mount(PurchaseOrder $purchaseOrder)
    {
        $this->mountCommon();
        
        $this->purchaseOrder = $purchaseOrder;
        $this->organization_id = $purchaseOrder->organization ? $purchaseOrder->organization->id : null;
        $this->organization_name = $purchaseOrder->organization ? $purchaseOrder->organization->name : null;
        $this->person_id = $purchaseOrder->person ? $purchaseOrder->person->id : null;
        $this->person_name = $purchaseOrder->person ? $purchaseOrder->person->name : null;
        $this->reference = $purchaseOrder->reference;
        $this->currency = $purchaseOrder->currency;
        $this->issue_date = $purchaseOrder->issue_date->format('Y-m-d') ?? null;
        $this->delivery_date = $purchaseOrder->delivery_date->format('Y-m-d') ?? null;
        $this->terms = $purchaseOrder->terms;
        $this->pipeline_stage_id = $purchaseOrder->pipelineStage->id ?? null;
        $this->user_owner_id = $purchaseOrder->ownerUser->id ?? null;
        $this->delivery_instructions = $purchaseOrder->delivery_instructions ?? null;
        
        if ($purchaseOrder->address) {
            $this->delivery_address = $purchaseOrder->address->id;
        }
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

        $this->purchaseOrderService->update($request, $this->purchaseOrder, $person ?? null, $organization ?? null); 

        $this->success(
            ucfirst(trans('laravel-crm::lang.purchase_order_updated')),
            redirectTo: route('laravel-crm.purchase-orders.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.purchase-orders.purchase-order-edit');
    }
}
