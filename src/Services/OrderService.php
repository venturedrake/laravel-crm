<?php

namespace VentureDrake\LaravelCrm\Services;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Address;
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

    public function create($request, $person = null, $organisation = null, $client = null)
    {
        $order = Order::create([
            'lead_id' => $request->lead_id ?? null,
            'deal_id' => $request->deal_id ?? null,
            'quote_id' => $request->quote_id ?? null,
            'person_id' => $person->id ?? null,
            'organisation_id' => $organisation->id ?? null,
            'client_id' => $client->id ?? null,
            'quote_id' => $request->quote_id ?? null,
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
                if (isset($product['product_id']) && $product['product_id'] > 0 && $product['quantity'] > 0) {
                    $order->orderProducts()->create([
                        'product_id' => $product['product_id'],
                        'quantity' => $product['quantity'],
                        'price' => $product['unit_price'],
                        'amount' => $product['amount'],
                        'currency' => $request->currency,
                        'comments' => $product['comments'],
                    ]);
                }
            }
        }

        $this->updateOrderAddresses($order, $request->addresses);

        return $order;
    }

    public function update($request, Order $order, $person = null, $organisation = null, $client = null)
    {
        $order->update([
            'person_id' => $person->id ?? null,
            'organisation_id' => $organisation->id ?? null,
            'client_id' => $client->id ?? null,
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
            $orderProductIds = [];
            
            foreach ($request->products as $product) {
                if (isset($product['order_product_id']) && $orderProduct = OrderProduct::find($product['order_product_id'])) {
                    $orderProductIds[] = $product['order_product_id'];

                    if (! isset($product['product_id']) || $product['quantity'] == 0) {
                        $orderProduct->delete();
                    } else {
                        $orderProduct->update([
                            'product_id' => $product['product_id'],
                            'quantity' => $product['quantity'],
                            'price' => $product['unit_price'],
                            'amount' => $product['amount'],
                            'currency' => $request->currency,
                            'comments' => $product['comments'],
                        ]);
                    }
                } elseif (isset($product['product_id']) && $product['product_id'] > 0 && $product['quantity'] > 0) {
                    $orderProduct = $order->orderProducts()->create([
                        'product_id' => $product['product_id'],
                        'quantity' => $product['quantity'],
                        'price' => $product['unit_price'],
                        'amount' => $product['amount'],
                        'currency' => $request->currency,
                        'comments' => $product['comments'],
                    ]);

                    $orderProductIds[] = $orderProduct->id;
                }
            }

            foreach ($order->orderProducts as $orderProduct) {
                if (! in_array($orderProduct->id, $orderProductIds)) {
                    $orderProduct->delete();
                }
            }
        }

        $this->updateOrderAddresses($order, $request->addresses);

        return $order;
    }

    protected function updateOrderAddresses($order, $addresses)
    {
        $addressIds = [];

        if ($addresses) {
            foreach ($addresses as $addressRequest) {
                if ($addressRequest['id'] && $address = Address::find($addressRequest['id'])) {
                    $address->update([
                        'address_type_id' => $addressRequest['type'] ?? null,
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

                    $addressIds[] = $address->id;
                } else {
                    $address = $order->addresses()->create([
                        'external_id' => Uuid::uuid4()->toString(),
                        'address_type_id' => $addressRequest['type'] ?? null,
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

                    $addressIds[] = $address->id;
                }
            }
        }

        foreach ($order->addresses as $address) {
            if (! in_array($address->id, $addressIds)) {
                $address->delete();
            }
        }
    }
}
