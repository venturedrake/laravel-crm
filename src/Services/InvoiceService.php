<?php

namespace VentureDrake\LaravelCrm\Services;

use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\InvoiceProduct;
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
                $invoice->invoiceLines()->create([
                    'product_id' => $invoiceLine['product_id'],
                    'quantity' => $invoiceLine['quantity'],
                    'price' => $invoiceLine['price'],
                    'amount' => $invoiceLine['amount'],
                    'currency' => $request->currency,
                ]);
            }
        }

        return $invoice;
    }

    public function update($request, Invoice $invoice, $person = null, $organisation = null)
    {
        $invoice->update([
            'person_id' => $person->id ?? null,
            'organisation_id' => $organisation->id ?? null,
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

        $invoice->labels()->sync($request->labels ?? []);

        if (isset($request->products)) {
            foreach ($request->products as $product) {
                if (isset($product['invoice_product_id']) && $invoiceProduct = InvoiceProduct::find($product['invoice_product_id'])) {
                    $invoiceProduct->update([
                        'product_id' => $product['product_id'],
                        'quantity' => $product['quantity'],
                        'price' => $product['unit_price'],
                        'amount' => $product['amount'],
                        'currency' => $request->currency,
                    ]);
                } else {
                    $invoice->invoiceProducts()->create([
                        'product_id' => $product['product_id'],
                        'quantity' => $product['quantity'],
                        'price' => $product['unit_price'],
                        'amount' => $product['amount'],
                        'currency' => $request->currency,
                    ]);
                }
            }
        }

        return $invoice;
    }
}
