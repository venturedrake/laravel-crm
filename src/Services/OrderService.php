<?php

namespace VentureDrake\LaravelCrm\Services;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\OrderProduct;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\QuoteProduct;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Repositories\OrderRepository;
use VentureDrake\LaravelCrm\Support\FormOptions;
use VentureDrake\LaravelCrm\Support\Money;
use VentureDrake\LaravelCrm\Support\PdfTemplateRegistry;
use VentureDrake\LaravelCrm\Support\Quantity;
use VentureDrake\LaravelCrm\Traits\ResolvesProductVariation;

class OrderService
{
    use ResolvesProductVariation;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * LeadService constructor.
     */
    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * $form carries the order form toggles when the save comes from the
     * order form; a field it hides is left as stored. Callers that pass no
     * $form (the API, the legacy controller) write every field as before.
     */
    public function create($request, $person = null, $organization = null, $client = null, ?FormOptions $form = null)
    {
        $order = Order::create([
            'lead_id' => $request->lead_id ?? null,
            'deal_id' => $request->deal_id ?? null,
            'quote_id' => $request->quote_id ?? null,
            'person_id' => $person->id ?? null,
            'organization_id' => $organization->id ?? null,
            'client_id' => $client->id ?? null,
            'description' => $request->description,
            'reference' => $request->reference,
            'currency' => $request->currency,
            'subtotal' => $request->sub_total,
            'discount' => $request->discount,
            'tax' => $request->tax,
            'adjustments' => $request->adjustment,
            'total' => $request->total,
            'pdf_template' => PdfTemplateRegistry::sanitize($request->pdf_template ?? null),
            'user_owner_id' => $request->user_owner_id,
        ]);

        if (! $form || $form->shows('labels')) {
            $order->labels()->sync($request->labels ?? []);
        }

        if (isset($request->products)) {
            $orderProductOrder = 0;

            foreach ($request->products as $product) {
                $orderProductOrder++;

                if (isset($product['id']) && Quantity::isPositive($product['quantity'])) {
                    if (! Product::withTrashed()->find($product['id'])) {
                        $newProduct = $this->addProduct($product, $request);
                        $product['id'] = $newProduct->id;
                    }
                }

                if (isset($product['id']) && $product['id'] > 0 && Quantity::isPositive($product['quantity'])) {
                    $taxRate = 0;
                    if ($productForTax = Product::withTrashed()->find($product['id'])) {
                        if ($productForTax->taxRate) {
                            $taxRate = $productForTax->taxRate->rate;
                        } elseif ($productForTax->tax_rate) {
                            $taxRate = $productForTax->tax_rate;
                        } elseif ($taxRate = TaxRate::where('default', 1)->first()) {
                            $taxRate = $taxRate->rate;
                        } else {
                            $taxRate = optional(Setting::where('name', 'tax_rate')->first())->value ?? 0;
                        }
                    }

                    $order->orderProducts()->create([
                        'product_id' => $product['id'],
                        'product_variation_id' => $this->variationIdFor($product['id'], $product['product_variation_id'] ?? null, $this->sourceVariationId(QuoteProduct::class, $product['quote_product_id'] ?? null)),
                        'quantity' => $product['quantity'],
                        'price' => $product['unit_price'],
                        'amount' => $product['amount'],
                        'tax_rate' => $taxRate ?? 0,
                        'tax_amount' => (Money::toFloat($product['amount']) * 100) * ($taxRate / 100),
                        'currency' => $request->currency,
                        'comments' => $product['comments'],
                        'quote_product_id' => $product['quote_product_id'] ?? null,
                        'order' => $orderProductOrder,
                    ]);
                }
            }
        }

        if ($request->addresses) {
            foreach ($request->addresses as $addressRequest) {
                $address = $order->addresses()->create([
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

        return $order;
    }

    public function update($request, Order $order, $person = null, $organization = null, $client = null, ?FormOptions $form = null)
    {
        $attributes = [
            'person_id' => $person->id ?? null,
            'organization_id' => $organization->id ?? null,
            'client_id' => $client->id ?? null,
            'description' => $request->description,
            'reference' => $request->reference,
            'currency' => $request->currency,
            'subtotal' => $request->sub_total,
            'discount' => $request->discount,
            'tax' => $request->tax,
            'adjustments' => $request->adjustment,
            'total' => $request->total,
            'pdf_template' => PdfTemplateRegistry::resolveUpdate($request->pdf_template ?? null, $order->pdf_template),
            'user_owner_id' => $request->user_owner_id,
        ];

        if ($form && ! $form->shows('description')) {
            unset($attributes['description']);
        }

        $order->update($attributes);

        if (! $form || $form->shows('labels')) {
            $order->labels()->sync($request->labels ?? []);
        }

        if (isset($request->products)) {
            $orderProductIds = [];
            $orderProductOrder = 0;

            foreach ($request->products as $product) {
                $orderProductOrder++;

                if (isset($product['order_product_id']) && $orderProduct = OrderProduct::find($product['order_product_id'])) {
                    if (! isset($product['id']) || Quantity::isZero($product['quantity'])) {
                        $orderProduct->delete();
                    } else {
                        if (! Product::withTrashed()->find($product['id'])) {
                            $newProduct = $this->addProduct($product, $request);
                            $product['id'] = $newProduct->id;
                        }

                        if (isset($product['id']) && $product['id'] > 0 && Quantity::isPositive($product['quantity'])) {
                            $taxRate = 0;
                            if ($productForTax = Product::withTrashed()->find($product['id'])) {
                                if ($productForTax->taxRate) {
                                    $taxRate = $productForTax->taxRate->rate;
                                } elseif ($productForTax->tax_rate) {
                                    $taxRate = $productForTax->tax_rate;
                                } elseif ($taxRate = TaxRate::where('default', 1)->first()) {
                                    $taxRate = $taxRate->rate;
                                } else {
                                    $taxRate = optional(Setting::where('name', 'tax_rate')->first())->value ?? 0;
                                }
                            }

                            $orderProduct->update([
                                'product_id' => $product['id'],
                                'product_variation_id' => $this->variationIdFor($product['id'], $product['product_variation_id'] ?? null, $orderProduct->product_variation_id),
                                'quantity' => $product['quantity'],
                                'price' => $product['unit_price'],
                                'amount' => $product['amount'],
                                'tax_rate' => $taxRate ?? 0,
                                'tax_amount' => (Money::toFloat($product['amount']) * 100) * ($taxRate / 100),
                                'currency' => $request->currency,
                                'comments' => $product['comments'],
                                'order' => $orderProductOrder,
                            ]);

                            $orderProductIds[] = $orderProduct->id;
                        }
                    }
                } elseif (isset($product['id']) && Quantity::isPositive($product['quantity'])) {
                    if (! Product::withTrashed()->find($product['id'])) {
                        $newProduct = $this->addProduct($product, $request);
                        $product['id'] = $newProduct->id;
                    }

                    if (isset($product['id']) && $product['id'] > 0 && Quantity::isPositive($product['quantity'])) {
                        $taxRate = 0;
                        if ($productForTax = Product::withTrashed()->find($product['id'])) {
                            if ($productForTax->taxRate) {
                                $taxRate = $productForTax->taxRate->rate;
                            } elseif ($productForTax->tax_rate) {
                                $taxRate = $productForTax->tax_rate;
                            } elseif ($taxRate = TaxRate::where('default', 1)->first()) {
                                $taxRate = $taxRate->rate;
                            } else {
                                $taxRate = optional(Setting::where('name', 'tax_rate')->first())->value ?? 0;
                            }
                        }

                        $orderProduct = $order->orderProducts()->create([
                            'product_id' => $product['id'],
                            'product_variation_id' => $this->variationIdFor($product['id'], $product['product_variation_id'] ?? null),
                            'quantity' => $product['quantity'],
                            'price' => $product['unit_price'],
                            'amount' => $product['amount'],
                            'tax_rate' => $taxRate ?? 0,
                            'tax_amount' => (Money::toFloat($product['amount']) * 100) * ($taxRate / 100),
                            'currency' => $request->currency,
                            'comments' => $product['comments'],
                            'order' => $orderProductOrder,
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

        $this->updateOrderAddresses($order, $request->addresses, $form?->hiddenAddressTypeIds() ?? []);

        return $order;
    }

    /**
     * Addresses of a type in $keepTypeIds are never deleted: the form hides
     * that type, so it was not submitted rather than removed.
     */
    protected function updateOrderAddresses($order, $addresses, array $keepTypeIds = [])
    {
        if ($addresses === null) {
            return;
        }

        $addressIds = [];

        if ($addresses) {
            foreach ($addresses as $addressRequest) {
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
                        'primary' => ((isset($addressRequest['primary']) && $addressRequest['primary'] == 'on') ? 1 : 0),
                    ]);

                    $addressIds[] = $address->id;
                } else {
                    $address = $order->addresses()->create([
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
                        'primary' => ((isset($addressRequest['primary']) && $addressRequest['primary'] == 'on') ? 1 : 0),
                    ]);

                    $addressIds[] = $address->id;
                }
            }
        }

        foreach ($order->addresses as $address) {
            if (! in_array($address->id, $addressIds) && ! in_array($address->address_type_id, $keepTypeIds)) {
                $address->delete();
            }
        }
    }

    protected function addProduct($product, $request)
    {
        $newProduct = Product::create([
            'name' => $product['product_id'],
            'user_owner_id' => $request->user_owner_id,
        ]);

        $newProduct->productPrices()->create([
            'unit_price' => $product['unit_price'],
            'currency' => $request->currency,
        ]);

        return $newProduct;
    }
}
