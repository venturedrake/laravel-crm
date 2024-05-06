<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;

class LiveDeliveryDetails extends Component
{
    public $deliveryType;

    public $purchaseOrder;

    public $addresses;

    public $purchaseOrderTerms;

    public $purchaseOrderDeliveryInstructions;

    public function mount($purchaseOrder, $addresses, $purchaseOrderTerms, $purchaseOrderDeliveryInstructions)
    {
        $this->deliveryType = $purchaseOrder->delivery_type ?? 'deliver';
        $this->purchaseOrder = $purchaseOrder;
        $this->addresses = $addresses;
        $this->purchaseOrderTerms = $purchaseOrderTerms;
        $this->purchaseOrderDeliveryInstructions = $purchaseOrderDeliveryInstructions;
    }

    public function render()
    {
        return view('laravel-crm::livewire.delivery-details');
    }
}
