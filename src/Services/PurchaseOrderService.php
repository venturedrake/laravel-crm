<?php

namespace VentureDrake\LaravelCrm\Services;

use Carbon\Carbon;
use Dcblogdev\Xero\Facades\Xero;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\OrderProduct;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Models\XeroPurchaseOrder;
use VentureDrake\LaravelCrm\Repositories\PurchaseOrderRepository;
use VentureDrake\LaravelCrm\Support\AddressContent;
use VentureDrake\LaravelCrm\Support\DocumentTotals;
use VentureDrake\LaravelCrm\Support\LineAmount;
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
        return DB::transaction(function () use ($request, $person, $organization) {
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

            // The form validates the address; a caller that skips it (the legacy
            // controller) gets a PO with no address rather than a 500 on null.
            $address = ($request->delivery_type == 'deliver' && $request->delivery_address)
                ? $this->teamDeliveryAddress($request->delivery_address)
                : null;

            if ($address) {
                $this->copyDeliveryAddress($purchaseOrder, $address);
            }

            if (isset($request->products)) {
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

                        $purchaseOrder->purchaseOrderLines()->create([
                            'product_id' => $purchaseOrderLine['id'],
                            'product_variation_id' => $this->variationIdFor($purchaseOrderLine['id'], $purchaseOrderLine['product_variation_id'] ?? null, $this->sourceVariationId(OrderProduct::class, $purchaseOrderLine['order_product_id'] ?? null)),
                            'quantity' => $purchaseOrderLine['quantity'],
                            'price' => $purchaseOrderLine['unit_price'],
                            'amount' => $purchaseOrderLine['amount'],
                            'tax_rate' => $taxRate ?? 0,
                            'tax_amount' => LineAmount::taxCents(Money::toInteger($purchaseOrderLine['amount']) ?? 0, $taxRate ?? 0) / 100,
                            'currency' => $request->currency,
                            'order_product_id' => $purchaseOrderLine['order_product_id'] ?? null,
                            'comments' => $purchaseOrderLine['comments'] ?? null,
                            'order' => ++$purchaseOrderLineOrder,
                        ]);
                    }
                }

            }

            $this->syncTotals($purchaseOrder);

            // After the commit, so a save that rolls back - including one
            // wrapped by the form around custom fields - never leaves a
            // purchase order in Xero with nothing behind it in the CRM.
            DB::afterCommit(fn () => $this->pushToXero($purchaseOrder));

            return $purchaseOrder;
        });
    }

    public function update($request, PurchaseOrder $purchaseOrder, $person = null, $organization = null)
    {
        return DB::transaction(function () use ($request, $purchaseOrder, $person, $organization) {
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
                'pdf_template' => PdfTemplateRegistry::resolveUpdate($request->pdf_template ?? null, $purchaseOrder->pdf_template),
                'user_owner_id' => $request->user_owner_id ?? auth()->user()->id,
            ]);

            if ($request->delivery_type == 'deliver' && $request->has('delivery_address')) {
                // The Livewire form sends the id of the address picked, as
                // create() takes it - never the address_* fields below, which
                // it used to wipe the PO's address with on every save.
                $this->syncDeliveryAddress($purchaseOrder, $request->delivery_address);
            } elseif ($request->delivery_type == 'deliver') {
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
                        'tax_amount' => LineAmount::taxCents(Money::toInteger($line['amount'] ?? null) ?? 0, $taxRate ?? 0) / 100,
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

            $this->syncTotals($purchaseOrder);

            return $purchaseOrder;
        });
    }

    /**
     * Push a newly created purchase order to Xero, when it is connected.
     */
    protected function pushToXero(PurchaseOrder $purchaseOrder): void
    {
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
    }

    /**
     * Point the PO at the address picked on the edit form. The PO holds its
     * own copy, so the edit form starts on that copy's id: left there, the
     * address is untouched. A different pick replaces the copy with a fresh
     * one. No pick, or an id that is not one of the team's addresses, keeps
     * what the PO has.
     */
    protected function syncDeliveryAddress(PurchaseOrder $purchaseOrder, $addressId): void
    {
        if (! $addressId || $purchaseOrder->address?->id == $addressId) {
            return;
        }

        if (! $address = $this->teamDeliveryAddress($addressId)) {
            return;
        }

        $purchaseOrder->address?->delete();

        $this->copyDeliveryAddress($purchaseOrder, $address);
    }

    /**
     * One of the team's own delivery addresses - the only ones a PO can be
     * delivered to. The Livewire form validates this too; the legacy
     * controller does not, so the service checks for every caller. One that
     * is only a country is not somewhere to deliver to.
     */
    protected function teamDeliveryAddress($addressId): ?Address
    {
        $address = app('laravel-crm.settings')->first('team')?->addresses()->find($addressId);

        return $address && AddressContent::isDeliverable($address) ? $address : null;
    }

    /**
     * Give the PO its own copy of an address, so a later edit to the team's
     * address does not rewrite where past orders were delivered.
     */
    protected function copyDeliveryAddress(PurchaseOrder $purchaseOrder, Address $address): void
    {
        $deliveryAddress = $address->replicate();
        $deliveryAddress->external_id = Uuid::uuid4()->toString();
        $deliveryAddress->created_at = now();
        $deliveryAddress->updated_at = now();
        $purchaseOrder->address()->save($deliveryAddress);
        $purchaseOrder->unsetRelation('address');
    }

    /**
     * Set the header totals from the lines actually saved. The request's
     * totals are only what the form last summed, and can count a row the
     * loop above dropped.
     */
    protected function syncTotals(PurchaseOrder $purchaseOrder): void
    {
        $totals = DocumentTotals::fromLines($purchaseOrder->purchaseOrderLines()->get());

        $purchaseOrder->update([
            'subtotal' => $totals['subtotal'] / 100,
            'tax' => $totals['tax'] / 100,
            'total' => ($totals['subtotal'] + $totals['tax']) / 100,
        ]);
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
