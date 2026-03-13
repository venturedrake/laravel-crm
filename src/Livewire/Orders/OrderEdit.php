<?php

namespace VentureDrake\LaravelCrm\Livewire\Orders;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Orders\Traits\HasOrderCommon;
use VentureDrake\LaravelCrm\Livewire\Traits\HasOrganizationSuggest;
use VentureDrake\LaravelCrm\Livewire\Traits\HasPersonSuggest;
use VentureDrake\LaravelCrm\Models\AddressType;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;

class OrderEdit extends Component
{
    use HasOrderCommon;
    use HasOrganizationSuggest;
    use HasPersonSuggest;

    public ?Order $order = null;

    protected $listeners = [
        'model-products-updated' => 'updateProducts',
    ];

    public function mount(Order $order)
    {
        $this->mountCommon();

        $this->order = $order;
        $this->organization_id = $order->organization ? $order->organization->id : null;
        $this->organization_name = $order->organization ? $order->organization->name : null;
        $this->person_id = $order->person ? $order->person->id : null;
        $this->person_name = $order->person ? $order->person->name : null;
        $this->description = $order->description;
        $this->reference = $order->reference;
        $this->currency = $order->currency;
        $this->pipeline_stage_id = $order->pipelineStage->id ?? null;
        $this->labels = $order->labels->pluck('id')->toArray();
        $this->user_owner_id = $order->ownerUser->id ?? null;

        $billingAddressTypeId = AddressType::where('name', 'Billing')->first()->id ?? null;
        $shippingAddressTypeId = AddressType::where('name', 'Shipping')->first()->id ?? null;

        foreach ($this->order->addresses as $address) {
            if ($address->address_type_id == $billingAddressTypeId) {
                $this->addresses['billing'] = [
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

        $this->sub_total = $order->subtotal / 100;
        $this->tax = $order->tax / 100;
        $this->total = $order->total / 100;
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

        $this->orderService->update($request, $this->order, $person ?? null, $organization ?? null);

        $this->success(
            ucfirst(trans('laravel-crm::lang.order_updated')),
            redirectTo: route('laravel-crm.orders.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.orders.order-edit');
    }
}
