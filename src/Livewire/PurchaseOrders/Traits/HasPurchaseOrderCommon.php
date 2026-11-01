<?php

namespace VentureDrake\LaravelCrm\Livewire\PurchaseOrders\Traits;

use Illuminate\Validation\Rule;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Livewire\Traits\HasLineItemQuantityRules;
use VentureDrake\LaravelCrm\Livewire\Traits\HasPdfTemplate;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Services\OrganizationService;
use VentureDrake\LaravelCrm\Services\PersonService;
use VentureDrake\LaravelCrm\Services\PurchaseOrderService;
use VentureDrake\LaravelCrm\Traits\HasCustomFormFields;

trait HasPurchaseOrderCommon
{
    use HasCustomFormFields;
    use HasLineItemQuantityRules;
    use HasPdfTemplate;
    use Toast;

    protected PurchaseOrderService $purchaseOrderService;

    protected PersonService $personService;

    protected OrganizationService $organizationService;

    public $person_id;

    public $person_name;

    public $organization_id;

    public $organization_name;

    public $reference;

    public $currency;

    public $issue_date;

    public $delivery_date;

    public $terms;

    public $pipeline;

    public $pipeline_stage_id;

    public $user_owner_id;

    public array $products;

    public $sub_total = 0;

    public $discount = 0;

    public $tax = 0;

    public $adjustment = 0;

    public $total = 0;

    public array $deliveryTypes = [
        ['id' => 'deliver', 'name' => 'Deliver'],
        ['id' => 'pickup', 'name' => 'Pickup'],
    ];

    public $delivery_type = 'deliver';

    public array $deliveryAddresses = [];

    public $delivery_address;

    public $delivery_instructions;

    protected function customFieldsModel(): string
    {
        return PurchaseOrder::class;
    }

    public function pdfTemplateDocType(): string
    {
        return 'purchase-order';
    }

    protected function rules()
    {
        return array_merge([
            'person_name' => 'required_without_all:organization_name,organization_id|max:255',
            'person_id' => 'required_without_all:organization_name,organization_id,person_name|max:255',
            'organization_name' => 'required_without_all:person_name,person_id|max:255',
            'organization_id' => 'required_without_all:person_name,person_id,organization_name|max:255',
            // A delivered PO needs an address, and only one the form offers.
            // The service used to call ->replicate() on whatever id came in,
            // so a missing or foreign one was a 500 after the PO was saved.
            'delivery_address' => ['nullable', 'required_if:delivery_type,deliver', Rule::in($this->allowedDeliveryAddressIds())],
        ], $this->lineItemQuantityRules(), $this->pdfTemplateRules(), $this->customFieldRules());
    }

    /**
     * The team's delivery addresses, plus - when editing - the PO's own copy
     * of the one it was saved with, which the edit form starts on.
     */
    protected function allowedDeliveryAddressIds(): array
    {
        $ids = array_column($this->deliveryAddresses, 'id');

        if (isset($this->purchaseOrder) && $this->purchaseOrder->address) {
            $ids[] = $this->purchaseOrder->address->id;
        }

        return $ids;
    }

    protected function messages()
    {
        return array_merge([
            'person_name.required_without_all' => 'The contact person field is required if no organization.',
            'organization_name.required_without_all' => 'The organization field is required if no contact person.',
            'person_id.required_without_all' => 'The contact person field is required if no organization.',
            'organization_id.required_without_all' => 'The organization field is required of no contact person.',
            'delivery_address.required_if' => ucfirst(__('laravel-crm::lang.delivery_address_required')),
            'delivery_address.in' => ucfirst(__('laravel-crm::lang.delivery_address_required')),
        ], $this->lineItemQuantityMessages(), $this->customFieldMessages());
    }

    protected function validationAttributes()
    {
        return $this->customFieldValidationAttributes();
    }

    public function boot(PurchaseOrderService $purchaseOrderService, PersonService $personService, OrganizationService $organizationService): void
    {
        $this->purchaseOrderService = $purchaseOrderService;
        $this->personService = $personService;
        $this->organizationService = $organizationService;
    }

    public function mountCommon($purchaseOrder = null)
    {
        $this->pipeline = Pipeline::where('model', get_class(new PurchaseOrder))->first();
        $this->mountPdfTemplate($purchaseOrder);

        $related = app('laravel-crm.settings')->first('team');

        foreach ($related->addresses as $address) {
            $this->deliveryAddresses[] = [
                'id' => $address->id,
                'name' => $address->address,
            ];
        }
    }

    public function updateProducts($products, $sub_total = 0, $tax = 0, $total = 0): void
    {
        $this->products = $products;
        $this->sub_total = $sub_total;
        $this->tax = $tax;
        $this->total = $total;
    }
}
