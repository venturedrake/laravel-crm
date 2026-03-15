<?php

namespace VentureDrake\LaravelCrm\Livewire\PurchaseOrders\Traits;

use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Services\OrganizationService;
use VentureDrake\LaravelCrm\Services\PersonService;
use VentureDrake\LaravelCrm\Services\PurchaseOrderService;

trait HasPurchaseOrderCommon
{
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

    protected function rules()
    {
        return [
            'person_name' => 'required_without_all:organization_name,organization_id|max:255',
            'person_id' => 'required_without_all:organization_name,organization_id,person_name|max:255',
            'organization_name' => 'required_without_all:person_name,person_id|max:255',
            'organization_id' => 'required_without_all:person_name,person_id,organization_name|max:255',
        ];
    }

    protected function messages()
    {
        return [
            'person_name.required_without_all' => 'The contact person field is required if no organization.',
            'organization_name.required_without_all' => 'The organization field is required if no contact person.',
            'person_id.required_without_all' => 'The contact person field is required if no organization.',
            'organization_id.required_without_all' => 'The organization field is required of no contact person.',
        ];
    }

    public function boot(PurchaseOrderService $purchaseOrderService, PersonService $personService, OrganizationService $organizationService): void
    {
        $this->purchaseOrderService = $purchaseOrderService;
        $this->personService = $personService;
        $this->organizationService = $organizationService;
    }

    public function mountCommon()
    {
        $this->pipeline = Pipeline::where('model', get_class(new PurchaseOrder))->first();

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
