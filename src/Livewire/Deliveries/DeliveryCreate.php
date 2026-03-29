<?php

namespace VentureDrake\LaravelCrm\Livewire\Deliveries;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Deliveries\Traits\HasDeliveryCommon;
use VentureDrake\LaravelCrm\Models\AddressType;
use VentureDrake\LaravelCrm\Models\Order;

class DeliveryCreate extends Component
{
    use HasDeliveryCommon;

    public ?Order $order = null;

    public $order_id;

    protected $listeners = [
        'model-products-updated' => 'updateProducts',
    ];

    public function mount()
    {
        $this->mountCommon();

        $this->pipeline_stage_id = $this->pipeline->pipelineStages->first()->id ?? null;
        $this->user_owner_id = auth()->user()->id;

        $this->addresses['shipping']['address_type_id'] = AddressType::where('name', 'Shipping')->first()->id ?? null;
        $this->addresses['shipping']['country'] = app('laravel-crm.settings')->get('country', 'United States');

        switch ($this->fromModelType) {
            case 'order':
                if ($order = Order::find($this->fromModelId)) {
                    $this->fromModel = $order;
                    $this->order = $order;
                    $this->order_id = $order->id;
                }
                break;
        }

        if (request()->has('stage')) {
            $this->pipeline_stage_id = request()->stage;
        }
    }

    public function save()
    {
        // $this->validate();

        // Create a request object to pass to services
        $request = \VentureDrake\LaravelCrm\Http\Helpers\PublicProperties\asRequest($this);

        $this->deliveryService->create($request);

        $this->success(
            ucfirst(trans('laravel-crm::lang.delivery_created')),
            redirectTo: route('laravel-crm.deliveries.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.deliveries.delivery-create');
    }
}
