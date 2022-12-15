<?php

namespace VentureDrake\LaravelCrm\Services;

use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\OrderProduct;
use VentureDrake\LaravelCrm\Repositories\OrderRepository;

class OrderService
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * LeadService constructor.
     * @param OrderRepository $orderRepository
     */
    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function create($request, $person = null, $organisation = null)
    {
        $order = Order::create([
            'lead_id' => $request->lead_id ?? null,
            'person_id' => $person->id ?? null,
            'organisation_id' => $organisation->id ?? null,
            'description' => $request->description,
            'reference' => $request->reference,
            'currency' => $request->currency,
            'subtotal' => $request->sub_total,
            'discount' => $request->discount,
            'tax' => $request->tax,
            'adjustments' => $request->adjustment,
            'total' => $request->total,
            'user_owner_id' => $request->user_owner_id,
        ]);

        $order->labels()->sync($request->labels ?? []);

        if (isset($request->products)) {
            foreach ($request->products as $product) {
                $order->orderProducts()->create([
                    'product_id' => $product['product_id'],
                    'quantity' => $product['quantity'],
                    'price' => $product['unit_price'],
                    'amount' => $product['amount'],
                    'currency' => $request->currency,
                ]);
            }
        }

        return $order;
    }

    public function update($request, Order $order, $person = null, $organisation = null)
    {
        $order->update([
            'person_id' => $person->id ?? null,
            'organisation_id' => $organisation->id ?? null,
            'description' => $request->description,
            'reference' => $request->reference,
            'currency' => $request->currency,
            'subtotal' => $request->sub_total,
            'discount' => $request->discount,
            'tax' => $request->tax,
            'adjustments' => $request->adjustment,
            'total' => $request->total,
            'user_owner_id' => $request->user_owner_id,
        ]);

        $order->labels()->sync($request->labels ?? []);

        if (isset($request->products)) {
            foreach ($request->products as $product) {
                if (isset($product['order_product_id']) && $orderProduct = OrderProduct::find($product['order_product_id'])) {
                    $orderProduct->update([
                        'product_id' => $product['product_id'],
                        'quantity' => $product['quantity'],
                        'price' => $product['unit_price'],
                        'amount' => $product['amount'],
                        'currency' => $request->currency,
                    ]);
                } else {
                    $order->orderProducts()->create([
                        'product_id' => $product['product_id'],
                        'quantity' => $product['quantity'],
                        'price' => $product['unit_price'],
                        'amount' => $product['amount'],
                        'currency' => $request->currency,
                    ]);
                }
            }
        }

        return $order;
    }
}
