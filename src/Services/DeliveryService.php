<?php

namespace VentureDrake\LaravelCrm\Services;

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
            'user_owner_id' => $request->user_owner_id ?? auth()->user()->id,
        ]);

        if (isset($request->products)) {
            foreach ($request->products as $product) {
                $delivery->deliveryProducts()->create([
                    'order_product_id' => $product['order_product_id'],
                ]);
            }
        }

        return $delivery;
    }

    public function update($request, Delivery $delivery, $person = null, $organisation = null)
    {
        /*$delivery->update([
            'person_id' => $person->id ?? null,
            'organisation_id' => $organisation->id ?? null,
            'reference' => $request->reference,
            'delivery_id' => $request->prefix.$request->number,
            'prefix' => $request->prefix,
            'number' => $request->number,
            'issue_date' => $request->issue_date,
            'due_date' => $request->due_date,
            'currency' => $request->currency,
            'terms' => $request->terms,
            'subtotal' => $request->sub_total,
            'tax' => $request->tax,
            'total' => $request->total,
            'amount_due' => $request->total - ($delivery->amount_paid / 100),
            'user_owner_id' => $request->user_owner_id ?? auth()->user()->id,
        ]);

        if (isset($request->deliveryLines)) {
            foreach ($request->deliveryLines as $line) {
                if (isset($line['delivery_line_id']) && $deliveryLine = DeliveryLine::find($line['delivery_line_id'])) {
                    $deliveryLine->update([
                        'product_id' => $line['product_id'],
                        'quantity' => $line['quantity'],
                        'price' => $line['price'],
                        'amount' => $line['amount'],
                        'currency' => $request->currency,
                    ]);
                } else {
                    $delivery->deliveryLines()->create([
                        'product_id' => $line['product_id'],
                        'quantity' => $line['quantity'],
                        'price' => $line['price'],
                        'amount' => $line['amount'],
                        'currency' => $request->currency,
                    ]);
                }
            }
        }

        return $delivery;*/
    }
}
