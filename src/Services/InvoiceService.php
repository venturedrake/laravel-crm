<?php

namespace VentureDrake\LaravelCrm\Services;

use Carbon\Carbon;
use Dcblogdev\Xero\Facades\Xero;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\InvoiceLine;
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
            'invoice_id' => $request->prefix.$request->number,
            'prefix' => $request->prefix,
            'number' => $request->number,
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
                if (isset($invoiceLine['product_id']) && $invoiceLine['product_id'] > 0 && $invoiceLine['quantity'] > 0) {
                    $invoice->invoiceLines()->create([
                        'product_id' => $invoiceLine['product_id'],
                        'quantity' => $invoiceLine['quantity'],
                        'price' => $invoiceLine['price'],
                        'amount' => $invoiceLine['amount'],
                        'currency' => $request->currency,
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
                    /*'AccountCode' => null*/
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
                    'Date' => Carbon::now()->format('Y-m-d'),
                    'DueDate' => Carbon::now()->addDays(30)->format('Y-m-d'),
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
            'invoice_id' => $request->prefix.$request->number,
            'prefix' => $request->prefix,
            'number' => $request->number,
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
            foreach ($request->invoiceLines as $line) {
                if (isset($line['invoice_line_id']) && $invoiceLine = InvoiceLine::find($line['invoice_line_id'])) {
                    $invoiceLine->update([
                        'product_id' => $line['product_id'],
                        'quantity' => $line['quantity'],
                        'price' => $line['price'],
                        'amount' => $line['amount'],
                        'currency' => $request->currency,
                    ]);
                } elseif (isset($line['product_id']) && $line['product_id'] > 0 && $line['quantity'] > 0) {
                    $invoice->invoiceLines()->create([
                        'product_id' => $line['product_id'],
                        'quantity' => $line['quantity'],
                        'price' => $line['price'],
                        'amount' => $line['amount'],
                        'currency' => $request->currency,
                    ]);
                }
            }
        }

        return $invoice;
    }
}
