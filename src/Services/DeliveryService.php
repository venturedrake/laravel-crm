<?php

namespace VentureDrake\LaravelCrm\Services;

use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Repositories\DeliveryRepository;
use VentureDrake\LaravelCrm\Support\AddressContent;
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
        return DB::transaction(function () use ($request, $withDeliveredOn) {
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
                    // An address left blank on the form still carries the
                    // prefilled country - it is not an address.
                    if (AddressContent::isBlank($addressRequest)) {
                        continue;
                    }

                    $delivery->addresses()->create([
                        'external_id' => Uuid::uuid4()->toString(),
                    ] + $this->addressAttributes($addressRequest));
                }
            }

            return $delivery;
        });
    }

    public function update($request, Delivery $delivery, $person = null, $organization = null, bool $withDeliveredOn = true)
    {
        return DB::transaction(function () use ($request, $delivery, $withDeliveredOn) {
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
                    $address = ($addressRequest['id'] ?? null)
                        ? $delivery->addresses()->find($addressRequest['id'])
                        : null;

                    if (AddressContent::isBlank($addressRequest)) {
                        // Cleared on the form: the delivery no longer has it.
                        $address?->delete();
                    } elseif ($address) {
                        $address->update($this->addressAttributes($addressRequest));
                    } else {
                        // A delivery saved without an address can be given one.
                        $delivery->addresses()->create([
                            'external_id' => Uuid::uuid4()->toString(),
                        ] + $this->addressAttributes($addressRequest));
                    }
                }
            }

            return $delivery;
        });
    }

    protected function addressAttributes(array $addressRequest): array
    {
        return [
            'address_type_id' => $addressRequest['address_type_id'] ?? null,
            'address' => $addressRequest['address'] ?? null,
            'name' => $addressRequest['name'] ?? null,
            'contact' => $addressRequest['contact'] ?? null,
            'phone' => $addressRequest['phone'] ?? null,
            'line1' => $addressRequest['line1'] ?? null,
            'line2' => $addressRequest['line2'] ?? null,
            'line3' => $addressRequest['line3'] ?? null,
            'city' => $addressRequest['city'] ?? null,
            'state' => $addressRequest['state'] ?? null,
            'code' => $addressRequest['code'] ?? null,
            'country' => $addressRequest['country'] ?? null,
            'primary' => true,
        ];
    }
}
