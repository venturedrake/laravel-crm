<?php

namespace VentureDrake\LaravelCrm\Services;

use Carbon\Carbon;
use Dcblogdev\Xero\Facades\Xero;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\InvoiceLine;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Models\XeroInvoice;
use VentureDrake\LaravelCrm\Repositories\InvoiceRepository;

class InvoiceService
{
    /**
     * @var InvoiceRepository
     */
    private $invoiceRepository;

    /**
     * LeadService constructor.
     * @param InvoiceRepository $invoiceRepository
     */
    public function __construct(InvoiceRepository $invoiceRepository)
    {
        $this->invoiceRepository = $invoiceRepository;
    }

    public function create($request, $person = null, $organisation = null)
    {
        $invoice = Invoice::create([
            'order_id' => $request->order_id ?? null,
            'person_id' => $person->id ?? null,
            'organisation_id' => $organisation->id ?? null,
            'reference' => $request->reference,
            'issue_date' => $request->issue_date,
            'due_date' => $request->due_date,
            'currency' => $request->currency,
            'terms' => $request->terms,
            'subtotal' => $request->sub_total,
            'tax' => $request->tax,
            'total' => $request->total,
            'user_owner_id' => $request->user_owner_id ?? auth()->user()->id,
        ]);

        if (isset($request->invoiceLines)) {
            foreach ($request->invoiceLines as $invoiceLine) {
                if(isset($invoiceLine['product_id']) && $invoiceLine['quantity'] > 0) {
                    if(! Product::find($invoiceLine['product_id'])) {
                        $newProduct = $this->addProduct($invoiceLine, $request);
                        $invoiceLine['product_id'] = $newProduct->id;
                    }
                }

                if (isset($invoiceLine['product_id']) && $invoiceLine['product_id'] > 0 && $invoiceLine['quantity'] > 0) {
                    if($product = Product::find($invoiceLine['product_id'])) {
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

                    $invoice->invoiceLines()->create([
                        'product_id' => $invoiceLine['product_id'],
                        'quantity' => $invoiceLine['quantity'],
                        'price' => $invoiceLine['price'],
                        'amount' => $invoiceLine['amount'],
                        'tax_rate' => $taxRate ?? 0,
                        'tax_amount' => $invoiceLine['amount'] * ($taxRate / 100),
                        'currency' => $request->currency,
                        'order_product_id' => $invoiceLine['order_product_id'] ?? null,
                        'comments' => $invoiceLine['comments'],
                    ]);
                }
            }
        }

        if (Xero::isConnected()) {
            $lineItems = [];

            foreach ($invoice->invoiceLines as $line) {
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
                $xeroInvoice = Xero::invoices()->store([
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
                ]);
            } catch (Exception $e) {
                //
            }
        }

        return $invoice;
    }

    public function update($request, Invoice $invoice, $person = null, $organisation = null)
    {
        $invoice->update([
            'person_id' => $person->id ?? null,
            'organisation_id' => $organisation->id ?? null,
            'reference' => $request->reference,
            'issue_date' => $request->issue_date,
            'due_date' => $request->due_date,
            'currency' => $request->currency,
            'terms' => $request->terms,
            'subtotal' => $request->sub_total,
            'tax' => $request->tax,
            'total' => $request->total,
            'amount_due' => $request->total - ($invoice->amount_paid / 100),
            'user_owner_id' => $request->user_owner_id ?? auth()->user()->id,
        ]);

        if (isset($request->invoiceLines)) {
            $invoiceLineIds = [];

            foreach ($request->invoiceLines as $line) {
                if (isset($line['invoice_line_id']) && $invoiceLine = InvoiceLine::find($line['invoice_line_id'])) {
                    if (! isset($line['product_id']) || $line['quantity'] == 0) {
                        $invoiceLine->delete();
                    } else {
                        if(! Product::find($line['product_id'])) {
                            $newProduct = $this->addProduct($line, $request);
                            $line['product_id'] = $newProduct->id;
                        }

                        if (isset($line['product_id']) && $line['product_id'] > 0 && $line['quantity'] > 0) {
                            if($product = Product::find($invoiceLine['product_id'])) {
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

                            $invoiceLine->update([
                                'product_id' => $line['product_id'],
                                'quantity' => $line['quantity'],
                                'price' => $line['price'],
                                'amount' => $line['amount'],
                                'tax_rate' => $taxRate ?? 0,
                                'tax_amount' => $line['amount'] * ($taxRate / 100),
                                'currency' => $request->currency,
                                'comments' => $line['comments'],
                            ]);

                            $invoiceLineIds[] = $invoiceLine->id;
                        }
                    }
                } elseif(isset($line['product_id']) && $line['quantity'] > 0) {
                    if(! Product::find($line['product_id'])) {
                        $newProduct = $this->addProduct($line, $request);
                        $line['product_id'] = $newProduct->id;
                    }

                    if (isset($line['product_id']) && $line['product_id'] > 0 && $line['quantity'] > 0) {
                        if($product = Product::find($invoiceLine['product_id'])) {
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

                        $invoiceLine = $invoice->invoiceLines()->create([
                            'product_id' => $line['product_id'],
                            'quantity' => $line['quantity'],
                            'price' => $line['price'],
                            'amount' => $line['amount'],
                            'tax_rate' => $taxRate ?? 0,
                            'tax_amount' => $line['amount'] * ($taxRate / 100),
                            'currency' => $request->currency,
                            'comments' => $line['comments'],
                        ]);

                        $invoiceLineIds[] = $invoiceLine->id;
                    }
                }
            }

            foreach ($invoice->invoiceLines as $invoiceLine) {
                if (! in_array($invoiceLine->id, $invoiceLineIds)) {
                    $invoiceLine->delete();
                }
            }
        }

        return $invoice;
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
