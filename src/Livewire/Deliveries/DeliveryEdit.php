<?php

namespace VentureDrake\LaravelCrm\Livewire\Deliveries;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Deliveries\Traits\HasDeliveryCommon;
use VentureDrake\LaravelCrm\Models\AddressType;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Order;

class DeliveryEdit extends Component
{
    use HasDeliveryCommon;

    public ?Order $order = null;

    public $order_id;

    public Delivery $delivery;

    protected $listeners = [
        'model-products-updated' => 'updateProducts',
    ];

    public function mount()
    {
        $this->mountCommon();

        $this->delivery_expected = $this->delivery->delivery_expected->format('Y-m-d') ?? null;
        $this->delivered_on = $this->delivery->delivered_on->format('Y-m-d') ?? null;
        $this->pipeline_stage_id = $this->delivery->pipelineStage->id ?? null;
        $this->user_owner_id = $this->delivery->ownerUser->id ?? null;

        $shippingAddressTypeId = AddressType::where('name', 'Shipping')->first()->id ?? null;

        foreach ($this->delivery->addresses as $address) {
            if ($address->address_type_id == $shippingAddressTypeId) {
                $this->addresses['shipping'] = [
                    'id' => $address->id,
                    'address_type_id' => $address->address_type_id,
                    'contact' => $address->contact,
                    'phone' => $address->phone,
                    'address' => $address->address,
                    'line1' => $address->line1,
                    'line2' => $address->line2,
                    'line3' => $address->line3,
                    'city' => $address->city,
                    'state' => $address->state,
                    'code' => $address->code,
                    'country' => $address->country,
                    'primary' => $address->primary,
                ];
            }
        }
    }

    public function save()
    {
        // $this->validate();

        // Create a request object to pass to services
        $request = \VentureDrake\LaravelCrm\Http\Helpers\PublicProperties\asRequest($this);

        $this->deliveryService->update($request, $this->delivery);

        $this->success(
            ucfirst(trans('laravel-crm::lang.delivery_updated')),
            redirectTo: route('laravel-crm.deliveries.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.deliveries.delivery-edit');
    }
}
