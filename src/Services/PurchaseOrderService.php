<?php

namespace VentureDrake\LaravelCrm\Services;

use Carbon\Carbon;
use Dcblogdev\Xero\Facades\Xero;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\PurchaseOrderLine;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Models\XeroInvoice;
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
            'delivery_instructions' => $request->delivery_instructions,
            'subtotal' => $request->sub_total,
            'tax' => $request->tax,
            'total' => $request->total,
            'user_owner_id' => $request->user_owner_id ?? auth()->user()->id,
        ]);

        if (isset($request->purchaseOrderLines)) {
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
        }

        if (Xero::isConnected()) {
            $lineItems = [];

            foreach ($purchaseOrder->purchaseOrderLines as $line) {
                $lineItems[] = [
                    'Description' => $line->product->name,
                    'Quantity' => $line->quantity,
                    'UnitAmount' => $line->price / 100,
                    'TaxType' => 'OUTPUT',
                    /*'TaxAmount' => ($line->tax_total->value / 100),*/
                    // 'LineAmount' => null,
                    'ItemCode' => $line->product->xeroItem->code ?? $line->product->code ?? null,
                    'AccountCode' => 200, // Added setting for this
                ];
            }

            try {
                /*$xeroInvoice = Xero::invoices()->store([
                    'Type' => 'ACCREC',
                    'Status' => 'AUTHORISED',
                    'Contact' => [
                        'ContactID' => $invoice->organisation->xeroContact->contact_id,
                    ],
                    'LineItems' => $lineItems ?? [],
                    'Date' => ($invoice->issue_date) ? $invoice->issue_date->format('Y-m-d') : Carbon::now()->format('Y-m-d'),
                    'DueDate' => ($invoice->due_date) ? $invoice->due_date->format('Y-m-d') : Carbon::now()->addDays(30)->format('Y-m-d'),
                    'Reference' => $invoice->reference,
                ]);

                XeroInvoice::create([
                    'xero_id' => $xeroInvoice['InvoiceID'],
                    'xero_type' => $xeroInvoice['Type'],
                    'number' => $xeroInvoice['InvoiceNumber'],
                    'reference' => $xeroInvoice['Reference'],
                    'invoice_id' => $invoice->id,
                    'status' => $xeroInvoice['Status'],
                ]);*/
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
            'delivery_instructions' => $request->delivery_instructions,
            'subtotal' => $request->sub_total,
            'tax' => $request->tax,
            'total' => $request->total,
            'amount_due' => $request->total - ($purchaseOrder->amount_paid / 100),
            'user_owner_id' => $request->user_owner_id ?? auth()->user()->id,
        ]);

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
        $taxRate = TaxRate::where('default', 1)->first();

        $newProduct = Product::create([
            'name' => $product['product_id'],
            'tax_rate_id' => $taxRate->id ?? null,
            'tax_rate' => $taxRate->id ?? Setting::where('name', 'tax_rate')->first()->value ?? null,
            'user_owner_id' => $request->user_owner_id ?? auth()->user()->id,
        ]);

        $newProduct->productPrices()->create([
            'unit_price' => $product['price'],
            'currency' => $request->currency,
        ]);

        return $newProduct;
    }
}
