<?php

namespace VentureDrake\LaravelCrm\Services;

use Carbon\Carbon;
use Dcblogdev\Xero\Facades\Xero;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\OrderProduct;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Models\XeroPurchaseOrder;
use VentureDrake\LaravelCrm\Repositories\PurchaseOrderRepository;
use VentureDrake\LaravelCrm\Support\Money;
use VentureDrake\LaravelCrm\Support\PdfTemplateRegistry;
use VentureDrake\LaravelCrm\Support\Quantity;
use VentureDrake\LaravelCrm\Support\XeroIntegration;
use VentureDrake\LaravelCrm\Support\XeroLineItem;
use VentureDrake\LaravelCrm\Traits\ResolvesProductVariation;

class PurchaseOrderService
{
    use ResolvesProductVariation;

    /**
     * @var PurchaseOrderRepository
     */
    private $purchaseOrderRepository;

    /**
     * LeadService constructor.
     */
    public function __construct(PurchaseOrderRepository $purchaseOrderRepository)
    {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
    }

    public function create($request, $person = null, $organization = null)
    {
        $purchaseOrder = PurchaseOrder::create([
            'order_id' => $request->order_id ?? null,
            'person_id' => $person->id ?? null,
            'organization_id' => $organization->id ?? null,
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
            'pdf_template' => PdfTemplateRegistry::sanitize($request->pdf_template ?? null),
            'user_owner_id' => $request->user_owner_id ?? auth()->user()->id,
        ]);

        if ($request->delivery_type == 'deliver') {
            $deliveryAddress = Address::find($request->delivery_address)->replicate();
            $deliveryAddress->external_id = Uuid::uuid4()->toString();
            $deliveryAddress->created_at = now();
            $deliveryAddress->updated_at = now();
            $purchaseOrder->address()->save($deliveryAddress);
        }

        if (isset($request->products)) {
            $subTotal = 0;
            $tax = 0;
            $total = 0;
            $purchaseOrderLineOrder = 0;

            foreach ($request->products as $purchaseOrderLine) {
                if (isset($purchaseOrderLine['id']) && Quantity::isPositive($purchaseOrderLine['quantity'])) {
                    if (! Product::withTrashed()->find($purchaseOrderLine['id'])) {
                        $newProduct = $this->addProduct($purchaseOrderLine, $request);
                        $purchaseOrderLine['id'] = $newProduct->id;
                    }
                }

                if (isset($purchaseOrderLine['id']) && $purchaseOrderLine['id'] > 0 && Quantity::isPositive($purchaseOrderLine['quantity'])) {
                    $taxRate = 0;
                    if ($product = Product::withTrashed()->find($purchaseOrderLine['id'])) {
                        if ($product->taxRate) {
                            $taxRate = $product->taxRate->rate;
                        } elseif ($product->tax_rate) {
                            $taxRate = $product->tax_rate;
                        } elseif ($taxRate = TaxRate::where('default', 1)->first()) {
                            $taxRate = $taxRate->rate;
                        } else {
                            $taxRate = optional(Setting::where('name', 'tax_rate')->first())->value ?? 0;
                        }
                    }

                    $subTotal += Money::toFloat($purchaseOrderLine['amount']);
                    $tax += Money::toFloat($purchaseOrderLine['amount']) * ($taxRate / 100);
                    $total += (Money::toFloat($purchaseOrderLine['amount']) + (Money::toFloat($purchaseOrderLine['amount']) * ($taxRate / 100)));

                    $purchaseOrder->purchaseOrderLines()->create([
                        'product_id' => $purchaseOrderLine['id'],
                        'product_variation_id' => $this->variationIdFor($purchaseOrderLine['id'], $purchaseOrderLine['product_variation_id'] ?? null, $this->sourceVariationId(OrderProduct::class, $purchaseOrderLine['order_product_id'] ?? null)),
                        'quantity' => $purchaseOrderLine['quantity'],
                        'price' => $purchaseOrderLine['unit_price'],
                        'amount' => $purchaseOrderLine['amount'],
                        'tax_rate' => $taxRate ?? 0,
                        'tax_amount' => Money::toFloat($purchaseOrderLine['amount']) * ($taxRate / 100),
                        'currency' => $request->currency,
                        'order_product_id' => $purchaseOrderLine['order_product_id'] ?? null,
                        'comments' => $purchaseOrderLine['comments'] ?? null,
                        'order' => ++$purchaseOrderLineOrder,
                    ]);
                }
            }

            if (! $request->total) {
                $purchaseOrder->update([
                    'subtotal' => $subTotal,
                    'tax' => $tax,
                    'total' => $total,
                ]);
            }
        }

        if (XeroIntegration::connected()) {
            $lineItems = [];

            foreach ($purchaseOrder->purchaseOrderLines as $line) {
                $lineItems[] = [
                    'Description' => XeroLineItem::description($line),
                    'Quantity' => $line->quantity,
                    'UnitAmount' => $line->price / 100,
                    'TaxType' => 'INPUT',
                    /* 'TaxAmount' => ($line->tax_total->value / 100), */
                    // 'LineAmount' => null,
                    'ItemCode' => XeroLineItem::itemCode($line),
                    'AccountCode' => $line->product->purchase_account ?? 300,
                ];
            }

            try {
                $xeroPurchaseOrder = Xero::post('PurchaseOrders', $array = [
                    'Status' => 'AUTHORISED',
                    'Contact' => [
                        'ContactID' => $purchaseOrder->organization->xeroContact->contact_id,
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

    public function update($request, PurchaseOrder $purchaseOrder, $person = null, $organization = null)
    {
        $purchaseOrder->update([
            'person_id' => $person->id ?? null,
            'organization_id' => $organization->id ?? null,
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
            'amount_due' => Money::toFloat($request->total) - ($purchaseOrder->amount_paid / 100),
            'pdf_template' => PdfTemplateRegistry::resolveUpdate($request->pdf_template ?? null, $purchaseOrder->pdf_template),
            'user_owner_id' => $request->user_owner_id ?? auth()->user()->id,
        ]);

        if ($request->delivery_type == 'deliver') {
            if (! $purchaseOrder->address) {
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

        // Rows come from crm-model-products with the same keys create() reads:
        // the product in `id`, the line in `purchase_order_line_id`. This used
        // to read `purchaseOrderLines`, which the form has never sent, so line
        // edits were silently dropped.
        if (isset($request->products)) {
            $purchaseOrderLineIds = [];
            $purchaseOrderLineOrder = 0;

            foreach ($request->products as $line) {
                $purchaseOrderLineOrder++;

                $purchaseOrderLine = isset($line['purchase_order_line_id'])
                    ? $purchaseOrder->purchaseOrderLines()->whereKey($line['purchase_order_line_id'])->first()
                    : null;

                if (! isset($line['id']) || ! Quantity::isPositive($line['quantity'] ?? null)) {
                    continue;
                }

                // withTrashed: a line whose product has since been deleted keeps
                // it, rather than reading as a typed-in name and spawning a new
                // product called after the old id.
                if (! Product::withTrashed()->find($line['id'])) {
                    $line['id'] = $this->addProduct($line, $request)->id;
                }

                $taxRate = $this->resolveTaxRate($line['id']);

                $attributes = [
                    'product_id' => $line['id'],
                    'product_variation_id' => $this->variationIdFor(
                        $line['id'],
                        $line['product_variation_id'] ?? null,
                        $purchaseOrderLine
                            ? $purchaseOrderLine->product_variation_id
                            : $this->sourceVariationId(OrderProduct::class, $line['order_product_id'] ?? null)
                    ),
                    'quantity' => $line['quantity'],
                    'price' => $line['unit_price'] ?? null,
                    'amount' => $line['amount'] ?? null,
                    'tax_rate' => $taxRate,
                    'tax_amount' => Money::toFloat($line['amount'] ?? 0) * ($taxRate / 100),
                    'currency' => $request->currency,
                    'comments' => $line['comments'] ?? null,
                    'order' => $purchaseOrderLineOrder,
                ];

                if ($purchaseOrderLine) {
                    $purchaseOrderLine->update($attributes);
                } else {
                    $purchaseOrderLine = $purchaseOrder->purchaseOrderLines()->create($attributes + [
                        'order_product_id' => $line['order_product_id'] ?? null,
                    ]);
                }

                $purchaseOrderLineIds[] = $purchaseOrderLine->id;
            }

            // Anything not resubmitted - removed from the form, or zeroed - goes.
            foreach ($purchaseOrder->purchaseOrderLines()->get() as $purchaseOrderLine) {
                if (! in_array($purchaseOrderLine->id, $purchaseOrderLineIds)) {
                    $purchaseOrderLine->delete();
                }
            }
        }

        return $purchaseOrder;
    }

    /**
     * Resolve the applicable tax rate for a product, falling back through
     * product → default tax rate → settings → 0.
     */
    protected function resolveTaxRate($productId): float
    {
        if ($product = Product::withTrashed()->find($productId)) {
            if ($product->taxRate) {
                return (float) $product->taxRate->rate;
            }

            if ($product->tax_rate) {
                return (float) $product->tax_rate;
            }
        }

        if ($default = TaxRate::where('default', 1)->first()) {
            return (float) $default->rate;
        }

        return (float) (optional(Setting::where('name', 'tax_rate')->first())->value ?? 0);
    }

    /**
     * Create the product a dynamic-products line typed in by name. The row
     * carries the typed name in `id`, as on the other document forms.
     */
    protected function addProduct($product, $request)
    {
        $newProduct = Product::create([
            'name' => $product['id'],
            'user_owner_id' => $request->user_owner_id ?? auth()->user()->id,
        ]);

        $newProduct->productPrices()->create([
            'unit_price' => $product['unit_price'] ?? null,
            'currency' => $request->currency,
        ]);

        return $newProduct;
    }
}
