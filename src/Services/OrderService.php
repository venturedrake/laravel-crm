<?php

namespace VentureDrake\LaravelCrm\Services;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\OrderProduct;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\Setting;
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
                if(isset($product['product_id']) && $product['quantity'] > 0) {
                    if(! Product::find($product['product_id'])) {
                        $newProduct = $this->addProduct($product, $request);
                        $product['product_id'] = $newProduct->id;
                    }
                }

                if (isset($product['product_id']) && $product['product_id'] > 0 && $product['quantity'] > 0) {
                    if($productForTax = Product::find($product['product_id'])) {
                        if($productForTax->taxRate) {
                            $taxRate = $productForTax->taxRate->rate;
                        } elseif($productForTax->tax_rate) {
                            $taxRate = $productForTax->tax_rate;
                        } else {
                            $taxRate = Setting::where('name', 'tax_rate')->first()->value ?? 0;
                        }
                    }

                    $order->orderProducts()->create([
                        'product_id' => $product['product_id'],
                        'quantity' => $product['quantity'],
                        'price' => $product['unit_price'],
                        'amount' => $product['amount'],
                        'tax_rate' => $taxRate ?? 0,
                        'tax_amount' => ($product['amount'] * 100) * ($taxRate / 100),
                        'currency' => $request->currency,
                        'comments' => $product['comments'],
                        'quote_product_id' => $product['quote_product_id'] ?? null,
                    ]);
                }
            }
        }

        if ($request->addresses) {
            foreach ($request->addresses as $addressRequest) {
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
            }
        }

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
                    if (! isset($product['product_id']) || $product['quantity'] == 0) {
                        $orderProduct->delete();
                    } else {
                        if(! Product::find($product['product_id'])) {
                            $newProduct = $this->addProduct($product, $request);
                            $product['product_id'] = $newProduct->id;
                        }

                        if (isset($product['product_id']) && $product['product_id'] > 0 && $product['quantity'] > 0) {
                            if($productForTax = Product::find($product['product_id'])) {
                                if($productForTax->taxRate) {
                                    $taxRate = $productForTax->taxRate->rate;
                                } elseif($productForTax->tax_rate) {
                                    $taxRate = $productForTax->tax_rate;
                                } else {
                                    $taxRate = Setting::where('name', 'tax_rate')->first()->value ?? 0;
                                }
                            }

                            $orderProduct->update([
                                'product_id' => $product['product_id'],
                                'quantity' => $product['quantity'],
                                'price' => $product['unit_price'],
                                'amount' => $product['amount'],
                                'tax_rate' => $taxRate ?? 0,
                                'tax_amount' => ($product['amount'] * 100) * ($taxRate / 100),
                                'currency' => $request->currency,
                                'comments' => $product['comments'],
                            ]);

                            $orderProductIds[] = $orderProduct->id;
                        }
                    }
                } elseif(isset($product['product_id']) && $product['quantity'] > 0) {
                    if(! Product::find($product['product_id'])) {
                        $newProduct = $this->addProduct($product, $request);
                        $product['product_id'] = $newProduct->id;
                    }

                    if (isset($product['product_id']) && $product['product_id'] > 0 && $product['quantity'] > 0) {
                        if($productForTax = Product::find($product['product_id'])) {
                            if($productForTax->taxRate) {
                                $taxRate = $productForTax->taxRate->rate;
                            } elseif($productForTax->tax_rate) {
                                $taxRate = $productForTax->tax_rate;
                            } else {
                                $taxRate = Setting::where('name', 'tax_rate')->first()->value ?? 0;
                            }
                        }

                        $orderProduct = $order->orderProducts()->create([
                            'product_id' => $product['product_id'],
                            'quantity' => $product['quantity'],
                            'price' => $product['unit_price'],
                            'amount' => $product['amount'],
                            'tax_rate' => $taxRate ?? 0,
                            'tax_amount' => ($product['amount'] * 100) * ($taxRate / 100),
                            'currency' => $request->currency,
                            'comments' => $product['comments'],
                        ]);

                        $orderProductIds[] = $orderProduct->id;
                    }
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

    protected function addProduct($product, $request)
    {
        $newProduct = Product::create([
            'name' => $product['product_id'],
            'tax_rate' => Setting::where('name', 'tax_rate')->first()->value ?? null,
            'user_owner_id' => $request->user_owner_id,
        ]);

        $newProduct->productPrices()->create([
            'unit_price' => $product['unit_price'],
            'currency' => $request->currency,
        ]);

        return $newProduct;
    }
}
