<?php

namespace VentureDrake\LaravelCrm\Services;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Repositories\DeliveryRepository;

class DeliveryService
{
    /**
     * @var DeliveryRepository
     */
    private $deliveryRepository;

    /**
     * LeadService constructor.
     * @param DeliveryRepository $deliveryRepository
     */
    public function __construct(DeliveryRepository $deliveryRepository)
    {
        $this->deliveryRepository = $deliveryRepository;
    }

    public function create($request, $person = null, $organisation = null)
    {
        $delivery = Delivery::create([
            'order_id' => $request->order_id,
            'delivery_expected' => $request->delivery_expected,
            'delivered_on' => $request->delivered_on,
            'user_owner_id' => $request->user_owner_id ?? auth()->user()->id,
        ]);

        if (isset($request->products)) {
            foreach ($request->products as $product) {
                if($product['quantity'] > 0) {
                    $delivery->deliveryProducts()->create([
                        'order_product_id' => $product['order_product_id'],
                        'quantity' => $product['quantity'],
                    ]);
                }
            }
        }

        if ($request->addresses) {
            foreach ($request->addresses as $addressRequest) {
                $address = $delivery->addresses()->create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'address_type_id' => 6,
                    'address' => $addressRequest['address'] ?? null,
                    'name' => $addressRequest['name'] ?? null,
                    'contact' => $addressRequest['contact'] ?? null,
                    'phone' => $addressRequest['phone'] ?? null,
                    'line1' => $addressRequest['line1'],
                    'line2' => $addressRequest['line2'],
                    'line3' => $addressRequest['line3'],
                    'city' => $addressRequest['city'],
                    'state' => $addressRequest['state'],
                    'code' => $addressRequest['code'],
                    'country' => $addressRequest['country'],
                    'primary' => ((isset($addressRequest['primary']) && $addressRequest['primary'] == 'on') ? 1 : 0),
                ]);
            }
        }

        return $delivery;
    }

    public function update($request, Delivery $delivery, $person = null, $organisation = null)
    {
        $delivery->update([
            'delivery_expected' => $request->delivery_expected,
            'delivered_on' => $request->delivered_on,
        ]);

        if ($request->addresses) {
            foreach ($request->addresses as $addressRequest) {
                if ($addressRequest['id'] && $address = Address::find($addressRequest['id'])) {
                    $address->update([
                        'address_type_id' => 6,
                        'address' => $addressRequest['address'] ?? null,
                        'name' => $addressRequest['name'] ?? null,
                        'contact' => $addressRequest['contact'] ?? null,
                        'phone' => $addressRequest['phone'] ?? null,
                        'line1' => $addressRequest['line1'],
                        'line2' => $addressRequest['line2'],
                        'line3' => $addressRequest['line3'],
                        'city' => $addressRequest['city'],
                        'state' => $addressRequest['state'],
                        'code' => $addressRequest['code'],
                        'country' => $addressRequest['country'],
                        'primary' => ((isset($addressRequest['primary']) && $addressRequest['primary'] == 'on') ? 1 : 0),
                    ]);
                }
            }
        }

        return $delivery;
    }
}
