<?php

namespace VentureDrake\LaravelCrm\Livewire\Deliveries;

use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Pipeline;

class DeliveryShow extends Component
{
    use Toast;

    public Delivery $delivery;

    public ?Pipeline $pipeline = null;

    protected $listeners = [
        'refreshDelivery' => '$refresh',
    ];

    public function mount()
    {
        $this->pipeline = Pipeline::where('model', get_class(new Delivery))->first();

        $this->address = $this->delivery->getShippingAddress();
    }

    public function delete($id)
    {
        if ($delivery = Delivery::find($id)) {
            $delivery->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.delivery_deleted')), redirectTo: route('laravel-crm.deliveries.index'));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.deliveries.delivery-show');
    }
}
