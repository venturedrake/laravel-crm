<?php

namespace VentureDrake\LaravelCrm\Services;

use Ramsey\Uuid\Uuid;
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
            'external_id' => Uuid::uuid4()->toString(),
            'lead_id' => $request->lead_id ?? null,
            'person_id' => $person->id ?? null,
            'organisation_id' => $organisation->id ?? null,
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'expected_close' => $request->expected_close,
            'user_owner_id' => $request->user_owner_id,
        ]);

        $order->labels()->sync($request->labels ?? []);

        if (isset($request->item_order_product_id)) {
            foreach ($request->item_order_product_id as $orderProductKey => $orderProductValue) {
                $order->orderProducts()->create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'product_id' => $request->item_product_id[$orderProductKey],
                    'price' => $request->item_price[$orderProductKey],
                    'quantity' => $request->item_quantity[$orderProductKey],
                    'amount' => $request->item_amount[$orderProductKey],
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
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'expected_close' => $request->expected_close,
            'user_owner_id' => $request->user_owner_id,
        ]);

        $order->labels()->sync($request->labels ?? []);
        
        if (isset($request->item_order_product_id)) {
            foreach ($request->item_order_product_id as $orderProductKey => $orderProductValue) {
                $orderProduct = OrderProduct::find($orderProductValue);
                
                if ($orderProduct) {
                    $orderProduct->update([
                        'product_id' => $request->item_product_id[$orderProductKey],
                        'price' => $request->item_price[$orderProductKey],
                        'quantity' => $request->item_quantity[$orderProductKey],
                        'amount' => $request->item_amount[$orderProductKey],
                    ]);
                }
            }
        }
        
        return $order;
    }
}
