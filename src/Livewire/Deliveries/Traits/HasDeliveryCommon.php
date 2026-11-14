<?php

namespace VentureDrake\LaravelCrm\Livewire\Deliveries\Traits;

use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Livewire\Traits\HasLineItemQuantityRules;
use VentureDrake\LaravelCrm\Livewire\Traits\HasPdfTemplate;
use VentureDrake\LaravelCrm\Models\DeliveryProduct;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Services\DeliveryService;

trait HasDeliveryCommon
{
    use HasLineItemQuantityRules;
    use HasPdfTemplate;
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

    public function mountCommon($delivery = null)
    {
        $this->countries = \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\countries();
        $this->pipeline = Pipeline::where('model', get_class(new Invoice))->first();
        $this->mountPdfTemplate($delivery);
    }

    public function pdfTemplateDocType(): string
    {
        return 'delivery';
    }

    public function updateProducts($products): void
    {
        $this->products = $products;
    }

    /**
     * Delivery lines draw down the order line they were built from, so the
     * remaining quantity is recomputed against the deliveries already
     * raised rather than trusted from the submitted row.
     *
     * On edit the owner is the delivery itself: a line it already has is
     * only capped when raised, matching DeliveryService, so a delivery whose
     * order line was since reduced can still be saved.
     */
    protected function lineItemDrawdown(): ?array
    {
        $delivery = $this->delivery ?? null;

        return [
            'model' => DeliveryProduct::class,
            'relation' => 'delivery',
            'key' => 'delivery_product_id',
            'owner' => $delivery?->exists ? ['delivery_id' => $delivery->id] : null,
        ];
    }

    /**
     * The delivery form has never run validate(), so the quantity rules are
     * applied on their own rather than by uncommenting a bare validate() -
     * that would switch on the pdf template and custom field rules too,
     * which have never run here.
     *
     * Delivery from Order is one of the two flows that lost its bounded
     * dropdown, so the remaining quantity does need enforcing on submit.
     */
    protected function validateLineItemQuantities(): void
    {
        $this->validate($this->lineItemQuantityRules(), $this->lineItemQuantityMessages());
    }
}
