<?php

namespace VentureDrake\LaravelCrm\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\OrderProduct;
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

            if (isset($request->products)) {
                $this->updateLineQuantities($delivery, $request->products);
            }

            return $delivery;
        });
    }

    /**
     * Write the edited quantities back to the delivery's lines.
     *
     * Delivery edit has no add-row UI, so this only ever changes or removes
     * lines the delivery already has - a row whose delivery_product_id is
     * missing or belongs to another delivery is ignored.
     *
     * A line raised past what its order line has left is rejected here
     * rather than trusted from the form: the order line comes from the
     * stored delivery line, not the request, so neither a forged
     * order_product_id nor a raised quantity_max lifts the cap, and the
     * legacy PUT route is held to it too. Only raised lines are checked, so
     * a delivery whose order was later reduced can still be saved.
     */
    protected function updateLineQuantities(Delivery $delivery, $rows): void
    {
        $raised = [];

        foreach ($rows as $index => $row) {
            if (! is_array($row) || empty($row['delivery_product_id'])) {
                continue;
            }

            $quantity = $row['quantity'] ?? null;

            // A blank quantity is not a quantity of zero - leave the line be.
            if ($quantity === null || $quantity === '' || ! is_numeric($quantity)) {
                continue;
            }

            if (! $deliveryProduct = $delivery->deliveryProducts()->whereKey($row['delivery_product_id'])->first()) {
                continue;
            }

            if (! Quantity::isPositive($quantity)) {
                $deliveryProduct->delete();

                continue;
            }

            $before = $deliveryProduct->quantity;

            $deliveryProduct->update(['quantity' => $quantity]);

            if (Quantity::greaterThan($quantity, $before)) {
                $raised[$deliveryProduct->order_product_id] ??= $index;
            }
        }

        foreach ($raised as $orderProductId => $index) {
            $orderProduct = OrderProduct::withTrashed()->find($orderProductId);

            if ($orderProduct && Quantity::greaterThan(0, $orderProduct->deliveryRemainder())) {
                throw ValidationException::withMessages([
                    "products.$index.quantity" => ucfirst(__('laravel-crm::lang.quantity_exceeds_remaining', [
                        'remaining' => Quantity::format(max(0.0, $orderProduct->deliveryRemainder($delivery->id))),
                    ])),
                ]);
            }
        }
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
