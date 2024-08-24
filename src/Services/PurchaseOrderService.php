<?php

namespace VentureDrake\LaravelCrm\Services;

use Carbon\Carbon;
use Dcblogdev\Xero\Facades\Xero;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\PurchaseOrderLine;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Models\XeroPurchaseOrder;
use VentureDrake\LaravelCrm\Repositories\PurchaseOrderRepository;

class PurchaseOrderService
{
    /**
     * @var PurchaseOrderRepository
     */
    private $purchaseOrderRepository;

    /**
     * LeadService constructor.
     * @param PurchaseOrderRepository $purchaseOrderRepository
     */
    public function __construct(PurchaseOrderRepository $purchaseOrderRepository)
    {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
    }

    public function create($request, $person = null, $organisation = null)
    {
        $purchaseOrder = PurchaseOrder::create([
            'order_id' => $request->order_id ?? null,
            'person_id' => $person->id ?? null,
            'organisation_id' => $organisation->id ?? null,
            'reference' => $request->reference,
            'issue_date' => $request->issue_date,
            'delivery_date' => $request->delivery_date,
            'currency' => $request->currency,
            'delivery_type' => $request->delivery_type,
            'delivery_instructions' => $request->delivery_instructions,
            'terms' => $request->terms,
            'subtotal' => $request->sub_total ?? null,
            'tax' => $request->tax ?? null,
            'total' => $request->total ?? null,
            'user_owner_id' => $request->user_owner_id ?? auth()->user()->id,
        ]);

        if($request->delivery_type == 'deliver') {
            $deliveryAddress = Address::find($request->delivery_address)->replicate();
            $deliveryAddress->external_id = Uuid::uuid4()->toString();
            $deliveryAddress->created_at = now();
            $deliveryAddress->updated_at = now();
            $purchaseOrder->address()->save($deliveryAddress);
        }

        if (isset($request->purchaseOrderLines)) {
            $subTotal = 0;
            $tax = 0;
            $total = 0;

            foreach ($request->purchaseOrderLines as $purchaseOrderLine) {
                if(isset($purchaseOrderLine['product_id']) && $purchaseOrderLine['quantity'] > 0) {
                    if(! Product::find($purchaseOrderLine['product_id'])) {
                        $newProduct = $this->addProduct($purchaseOrderLine, $request);
                        $purchaseOrderLine['product_id'] = $newProduct->id;
                    }
                }

                if (isset($purchaseOrderLine['product_id']) && $purchaseOrderLine['product_id'] > 0 && $purchaseOrderLine['quantity'] > 0) {
                    if($product = Product::find($purchaseOrderLine['product_id'])) {
                        if($product->taxRate) {
                            $taxRate = $product->taxRate->rate;
                        } elseif($product->tax_rate) {
                            $taxRate = $product->tax_rate;
                        } elseif($taxRate = TaxRate::where('default', 1)->first()) {
                            $taxRate = $taxRate->rate;
                        } else {
                            $taxRate = Setting::where('name', 'tax_rate')->first()->value ?? 0;
                        }
                    }

                    $subTotal += $purchaseOrderLine['amount'];
                    $tax += $purchaseOrderLine['amount'] * ($taxRate / 100);
                    $total += ($purchaseOrderLine['amount'] + ($purchaseOrderLine['amount'] * ($taxRate / 100)));

                    $purchaseOrder->purchaseOrderLines()->create([
                        'product_id' => $purchaseOrderLine['product_id'],
                        'quantity' => $purchaseOrderLine['quantity'],
                        'price' => $purchaseOrderLine['price'],
                        'amount' => $purchaseOrderLine['amount'],
                        'tax_rate' => $taxRate ?? 0,
                        'tax_amount' => $purchaseOrderLine['amount'] * ($taxRate / 100),
                        'currency' => $request->currency,
                        'order_product_id' => $purchaseOrderLine['order_product_id'] ?? null,
                        'comments' => $purchaseOrderLine['comments'],
                    ]);
                }
            }

            if(! $request->total) {
                $purchaseOrder->update([
                    'subtotal' => $subTotal,
                    'tax' => $tax,
                    'total' => $total
                ]);
            }
        }

        if (Xero::isConnected()) {
            $lineItems = [];

            foreach ($purchaseOrder->purchaseOrderLines as $line) {
                $lineItems[] = [
                    'Description' => $line->product->name,
                    'Quantity' => $line->quantity,
                    'UnitAmount' => $line->price / 100,
                    'TaxType' => 'INPUT',
                    /*'TaxAmount' => ($line->tax_total->value / 100),*/
                    // 'LineAmount' => null,
                    'ItemCode' => $line->product->xeroItem->code ?? $line->product->code ?? null,
                    'AccountCode' => $line->product->purchase_account ?? 300,
                ];
            }

            try {
                $xeroPurchaseOrder = Xero::post('PurchaseOrders', $array = [
                    'Status' => 'AUTHORISED',
                    'Contact' => [
                        'ContactID' => $purchaseOrder->organisation->xeroContact->contact_id,
                    ],
                    'LineItems' => $lineItems ?? [],
                    'Date' => ($purchaseOrder->issue_date) ? $purchaseOrder->issue_date->format('Y-m-d') : Carbon::now()->format('Y-m-d'),
                    'DeliveryDate' => ($purchaseOrder->delivery_date) ? $purchaseOrder->delivery_date->format('Y-m-d') : Carbon::now()->addDays(30)->format('Y-m-d'),
                    'Reference' => $purchaseOrder->reference,
                    'DeliveryInstructions' => $purchaseOrder->delivery_instructions,
                ]);

                XeroPurchaseOrder::create([
                    'xero_id' => $xeroPurchaseOrder['body']['PurchaseOrders'][0]['PurchaseOrderID'],
                    'xero_type' => $xeroPurchaseOrder['body']['PurchaseOrders'][0]['Type'],
                    'number' => $xeroPurchaseOrder['body']['PurchaseOrders'][0]['PurchaseOrderNumber'],
                    'reference' => $xeroPurchaseOrder['body']['PurchaseOrders'][0]['Reference'],
                    'purchase_order_id' => $purchaseOrder->id,
                    'status' => $xeroPurchaseOrder['body']['PurchaseOrders'][0]['Status'],
                ]);
            } catch (Exception $e) {
                //
            }
        }

        return $purchaseOrder;
    }

    public function update($request, PurchaseOrder $purchaseOrder, $person = null, $organisation = null)
    {
        $purchaseOrder->update([
            'person_id' => $person->id ?? null,
            'organisation_id' => $organisation->id ?? null,
            'reference' => $request->reference,
            'issue_date' => $request->issue_date,
            'delivery_date' => $request->delivery_date,
            'currency' => $request->currency,
            'delivery_type' => $request->delivery_type,
            'delivery_instructions' => $request->delivery_instructions,
            'terms' => $request->terms,
            'subtotal' => $request->sub_total,
            'tax' => $request->tax,
            'total' => $request->total,
            'amount_due' => $request->total - ($purchaseOrder->amount_paid / 100),
            'user_owner_id' => $request->user_owner_id ?? auth()->user()->id,
        ]);

        if($request->delivery_type == 'deliver') {
            if(! $purchaseOrder->address) {
                $purchaseOrder->address()->create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'contact' => $request->address_contact,
                    'phone' => $request->address_phone,
                    'line1' => $request->address_line1,
                    'line2' => $request->address_line2,
                    'line3' => $request->address_line3,
                    'city' => $request->address_city,
                    'state' => $request->address_state,
                    'postal_code' => $request->address_code,
                    'country' => $request->address_country,
                ]);
            } else {
                $purchaseOrder->address->update([
                    'contact' => $request->address_contact,
                    'phone' => $request->address_phone,
                    'line1' => $request->address_line1,
                    'line2' => $request->address_line2,
                    'line3' => $request->address_line3,
                    'city' => $request->address_city,
                    'state' => $request->address_state,
                    'postal_code' => $request->address_code,
                    'country' => $request->address_country,
                ]);
            }
        }

        if (isset($request->purchaseOrderLines)) {
            $purchaseOrderLineIds = [];

            foreach ($request->purchaseOrderLines as $line) {
                if (isset($line['purchase_order_line_id']) && $purchaseOrderLine = PurchaseOrderLine::find($line['purchase_order_line_id'])) {
                    if (! isset($line['product_id']) || $line['quantity'] == 0) {
                        $purchaseOrderLine->delete();
                    } else {
                        if(! Product::find($line['product_id'])) {
                            $newProduct = $this->addProduct($line, $request);
                            $line['product_id'] = $newProduct->id;
                        }

                        if (isset($line['product_id']) && $line['product_id'] > 0 && $line['quantity'] > 0) {
                            if($product = Product::find($purchaseOrderLine['product_id'])) {
                                if($product->taxRate) {
                                    $taxRate = $product->taxRate->rate;
                                } elseif($product->tax_rate) {
                                    $taxRate = $product->tax_rate;
                                } elseif($taxRate = TaxRate::where('default', 1)->first()) {
                                    $taxRate = $taxRate->rate;
                                } else {
                                    $taxRate = Setting::where('name', 'tax_rate')->first()->value ?? 0;
                                }
                            }

                            $purchaseOrderLine->update([
                                'product_id' => $line['product_id'],
                                'quantity' => $line['quantity'],
                                'price' => $line['price'],
                                'amount' => $line['amount'],
                                'tax_rate' => $taxRate ?? 0,
                                'tax_amount' => $line['amount'] * ($taxRate / 100),
                                'currency' => $request->currency,
                                'comments' => $line['comments'],
                            ]);

                            $purchaseOrderLineIds[] = $purchaseOrderLine->id;
                        }
                    }
                } elseif(isset($line['product_id']) && $line['quantity'] > 0) {
                    if(! Product::find($line['product_id'])) {
                        $newProduct = $this->addProduct($line, $request);
                        $line['product_id'] = $newProduct->id;
                    }

                    if (isset($line['product_id']) && $line['product_id'] > 0 && $line['quantity'] > 0) {
                        if($product = Product::find($line['product_id'])) {
                            if($product->taxRate) {
                                $taxRate = $product->taxRate->rate;
                            } elseif($product->tax_rate) {
                                $taxRate = $product->tax_rate;
                            } elseif($taxRate = TaxRate::where('default', 1)->first()) {
                                $taxRate = $taxRate->rate;
                            } else {
                                $taxRate = Setting::where('name', 'tax_rate')->first()->value ?? 0;
                            }
                        }

                        $purchaseOrderLine = $purchaseOrder->purchaseOrderLines()->create([
                            'product_id' => $line['product_id'],
                            'quantity' => $line['quantity'],
                            'price' => $line['price'],
                            'amount' => $line['amount'],
                            'tax_rate' => $taxRate ?? 0,
                            'tax_amount' => $line['amount'] * ($taxRate / 100),
                            'currency' => $request->currency,
                            'comments' => $line['comments'],
                        ]);

                        $purchaseOrderLineIds[] = $purchaseOrderLine->id;
                    }
                }
            }

            foreach ($purchaseOrder->purchaseOrderLines as $purchaseOrderLine) {
                if (! in_array($purchaseOrderLine->id, $purchaseOrderLineIds)) {
                    $purchaseOrderLine->delete();
                }
            }
        }

        return $purchaseOrder;
    }

    protected function addProduct($product, $request)
    {
        $newProduct = Product::create([
            'name' => $product['product_id'],
            'user_owner_id' => $request->user_owner_id ?? auth()->user()->id,
        ]);

        $newProduct->productPrices()->create([
            'unit_price' => $product['price'],
            'currency' => $request->currency,
        ]);

        return $newProduct;
    }
}
