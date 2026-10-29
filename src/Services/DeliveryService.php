<?php

namespace VentureDrake\LaravelCrm\Services;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Repositories\DeliveryRepository;
use VentureDrake\LaravelCrm\Support\PdfTemplateRegistry;
use VentureDrake\LaravelCrm\Support\Quantity;

class DeliveryService
{
    /**
     * @var DeliveryRepository
     */
    private $deliveryRepository;

    /**
     * LeadService constructor.
     */
    public function __construct(DeliveryRepository $deliveryRepository)
    {
        $this->deliveryRepository = $deliveryRepository;
    }

    /**
     * $withDeliveredOn is false when the delivery screens hide "Delivered on"
     * (config laravel-crm.deliveries.delivered_on): the column is then left
     * as stored rather than written from a field the form never showed.
     */
    public function create($request, $person = null, $organization = null, bool $withDeliveredOn = true)
    {
        $attributes = [
            'order_id' => $request->order_id,
            'delivery_expected' => $request->delivery_expected,
            'delivered_on' => $request->delivered_on,
            'pdf_template' => PdfTemplateRegistry::sanitize($request->pdf_template ?? null),
            'user_owner_id' => $request->user_owner_id ?? auth()->user()->id,
        ];

        if (! $withDeliveredOn) {
            unset($attributes['delivered_on']);
        }

        $delivery = Delivery::create($attributes);

        if (isset($request->products)) {
            foreach ($request->products as $product) {
                if (Quantity::isPositive($product['quantity'])) {
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
                    'address_type_id' => $addressRequest['address_type_id'] ?? null,
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
                    'primary' => true,
                ]);
            }
        }

        return $delivery;
    }

    public function update($request, Delivery $delivery, $person = null, $organization = null, bool $withDeliveredOn = true)
    {
        $attributes = [
            'delivery_expected' => $request->delivery_expected,
            'delivered_on' => $request->delivered_on,
            'pdf_template' => PdfTemplateRegistry::resolveUpdate($request->pdf_template ?? null, $delivery->pdf_template),
        ];

        if (! $withDeliveredOn) {
            unset($attributes['delivered_on']);
        }

        $delivery->update($attributes);

        if ($request->addresses) {
            foreach ($request->addresses as $addressRequest) {
                if ($addressRequest['id'] && $address = Address::find($addressRequest['id'])) {
                    $address->update([
                        'address_type_id' => $addressRequest['address_type_id'] ?? null,
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
                        'primary' => true,
                    ]);
                }
            }
        }

        return $delivery;
    }
}
