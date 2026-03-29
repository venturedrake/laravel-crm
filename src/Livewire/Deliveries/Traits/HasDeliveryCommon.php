<?php

namespace VentureDrake\LaravelCrm\Livewire\Deliveries\Traits;

use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Services\DeliveryService;

trait HasDeliveryCommon
{
    use Toast;

    protected DeliveryService $deliveryService;

    public $delivery_expected;

    public $delivered_on;

    public $pipeline;

    public $pipeline_stage_id;

    public $user_owner_id;

    public $countries;

    public array $addresses = [
        'shipping' => [
            'id' => null,
            'address_type_id',
            'contact' => null,
            'phone' => null,
            'line1' => null,
            'line2' => null,
            'line3' => null,
            'city' => null,
            'state' => null,
            'code' => null,
            'country' => null,
            'primary' => 1,
        ],
    ];

    public array $products;

    public $fromModelType = null;

    public $fromModelId = null;

    public $fromModel = null;

    public function boot(DeliveryService $deliveryService): void
    {
        $this->deliveryService = $deliveryService;
    }

    public function mountCommon()
    {
        $this->countries = \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\countries();
        $this->pipeline = Pipeline::where('model', get_class(new Invoice))->first();
    }

    public function updateProducts($products): void
    {
        $this->products = $products;
    }
}
