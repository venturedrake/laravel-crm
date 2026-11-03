<?php

namespace VentureDrake\LaravelCrm\Services;

use Carbon\Carbon;
use Dcblogdev\Xero\Facades\Xero;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\InvoiceLine;
use VentureDrake\LaravelCrm\Models\OrderProduct;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Models\XeroInvoice;
use VentureDrake\LaravelCrm\Repositories\InvoiceRepository;
use VentureDrake\LaravelCrm\Support\DocumentTotals;
use VentureDrake\LaravelCrm\Support\LineAmount;
use VentureDrake\LaravelCrm\Support\Money;
use VentureDrake\LaravelCrm\Support\PdfTemplateRegistry;
use VentureDrake\LaravelCrm\Support\Quantity;
use VentureDrake\LaravelCrm\Support\XeroIntegration;
use VentureDrake\LaravelCrm\Support\XeroLineItem;
use VentureDrake\LaravelCrm\Traits\ResolvesProductVariation;

class InvoiceService
{
    use ResolvesProductVariation;

    /**
     * @var InvoiceRepository
     */
    private $invoiceRepository;

    /**
     * LeadService constructor.
     */
    public function __construct(InvoiceRepository $invoiceRepository)
    {
        $this->invoiceRepository = $invoiceRepository;
    }

    public function create($request, $person = null, $organization = null)
    {
        return DB::transaction(function () use ($request, $person, $organization) {
            $invoice = Invoice::create([
                'order_id' => $request->order_id ?? null,
                'person_id' => $person->id ?? null,
                'organization_id' => $organization->id ?? null,
                'reference' => $request->reference,
                'issue_date' => $request->issue_date,
                'due_date' => $request->due_date,
                'currency' => $request->currency,
                'terms' => $request->terms,
                'subtotal' => $request->sub_total,
                'tax' => $request->tax,
                'total' => $request->total,
                // Nothing is paid yet; syncTotals() sets amount_due to the total.
                'amount_paid' => 0,
                'pdf_template' => PdfTemplateRegistry::sanitize($request->pdf_template ?? null),
                'user_owner_id' => $request->user_owner_id ?? auth()->user()->id,
            ]);

            if (isset($request->products)) {
                $invoiceLineOrder = 0;

                foreach ($request->products as $invoiceLine) {
                    $invoiceLineOrder++;

                    if (isset($invoiceLine['id']) && Quantity::isPositive($invoiceLine['quantity'])) {
                        if (! Product::withTrashed()->find($invoiceLine['id'])) {
                            $newProduct = $this->addProduct($invoiceLine, $request);
                            $invoiceLine['id'] = $newProduct->id;
                        }
                    }

                    if (isset($invoiceLine['id']) && $invoiceLine['id'] > 0 && Quantity::isPositive($invoiceLine['quantity'])) {
                        $taxRate = 0;
                        if ($product = Product::withTrashed()->find($invoiceLine['id'])) {
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

                        $invoice->invoiceLines()->create([
                            'product_id' => $invoiceLine['id'],
                            'product_variation_id' => $this->variationIdFor($invoiceLine['id'], $invoiceLine['product_variation_id'] ?? null, $this->sourceVariationId(OrderProduct::class, $invoiceLine['order_product_id'] ?? null)),
                            'quantity' => $invoiceLine['quantity'],
                            'price' => $invoiceLine['unit_price'],
                            ...$this->lineValues($invoiceLine, $taxRate),
                            'tax_rate' => $taxRate ?? 0,
                            'currency' => $request->currency,
                            'order_product_id' => $invoiceLine['order_product_id'] ?? null,
                            'comments' => $invoiceLine['comments'],
                            'order' => $invoiceLineOrder,
                        ]);
                    }
                }
            }

            $this->syncTotals($invoice);

            // After the commit, so a save that rolls back - including one
            // wrapped by the form around custom fields - never leaves a
            // invoice in Xero with nothing behind it in the CRM.
            DB::afterCommit(fn () => $this->pushToXero($invoice));

            return $invoice;
        });
    }

    public function update($request, Invoice $invoice, $person = null, $organization = null)
    {
        return DB::transaction(function () use ($request, $invoice, $person, $organization) {
            // A caller that opened its own transaction takes this first; the
            // API's is the outermost, so it is taken here. Taking it again
            // inside the same transaction is free.
            $this->lockForEdit($invoice);

            // What the lines came to before this edit - not the stored
            // header, which an older invoice can hold out of step with them.
            $linesBefore = DocumentTotals::fromLines($invoice->invoiceLines()->get());
            $settled = $this->isSettled($invoice);

            $invoice->update([
                'person_id' => $person->id ?? null,
                'organization_id' => $organization->id ?? null,
                'reference' => $request->reference,
                'issue_date' => $request->issue_date,
                'due_date' => $request->due_date,
                'currency' => $request->currency,
                'terms' => $request->terms,
                // subtotal, tax and total: syncTotals() sets them from the
                // lines, or leaves them be on a paid invoice.
                'pdf_template' => PdfTemplateRegistry::resolveUpdate($request->pdf_template ?? null, $invoice->pdf_template),
                'user_owner_id' => $request->user_owner_id ?? auth()->user()->id,
            ]);

            if (isset($request->products)) {
                $invoiceLineIds = [];
                $invoiceLineOrder = 0;

                foreach ($request->products as $line) {
                    $invoiceLineOrder++;

                    // This invoice's line only: a line id from elsewhere is
                    // treated as a new line here, not an edit of another
                    // invoice's.
                    if (isset($line['invoice_line_id']) && $invoiceLine = $invoice->invoiceLines()->whereKey($line['invoice_line_id'])->first()) {
                        if (! isset($line['id']) || Quantity::isZero($line['quantity'])) {
                            $invoiceLine->delete();
                        } else {
                            if (! Product::withTrashed()->find($line['id'])) {
                                $newProduct = $this->addProduct($line, $request);
                                $line['id'] = $newProduct->id;
                            }

                            if (isset($line['id']) && $line['id'] > 0 && Quantity::isPositive($line['quantity'])) {
                                $taxRate = 0;
                                // $line['id'] is the product - $invoiceLine is the line model, whose id is the line's own.
                                if ($product = Product::withTrashed()->find($line['id'])) {
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

                                // On a paid invoice a line re-sent at its saved price and
                                // quantity keeps its saved amount and tax. Repricing it
                                // would tax it at the product's current rate, and the
                                // form re-sends every line - so a rate change since the
                                // invoice was paid would have the lock refuse an edit to
                                // the terms. A line that did change is priced afresh, and
                                // the lock judges the result.
                                $pricing = $settled && $this->lineUnchanged($line, $invoiceLine)
                                    ? []
                                    : [...$this->lineValues($line, $taxRate), 'tax_rate' => $taxRate ?? 0];

                                $invoiceLine->update([
                                    'product_id' => $line['id'],
                                    'product_variation_id' => $this->variationIdFor($line['id'], $line['product_variation_id'] ?? null, $invoiceLine->product_variation_id),
                                    'quantity' => $line['quantity'],
                                    'price' => $line['unit_price'],
                                    ...$pricing,
                                    'currency' => $request->currency,
                                    'comments' => $line['comments'],
                                    'order' => $invoiceLineOrder,
                                ]);

                                $invoiceLineIds[] = $invoiceLine->id;
                            }
                        }
                    } elseif (isset($line['id']) && Quantity::isPositive($line['quantity'])) {
                        if (! Product::withTrashed()->find($line['id'])) {
                            $newProduct = $this->addProduct($line, $request);
                            $line['id'] = $newProduct->id;
                        }

                        if (isset($line['id']) && $line['id'] > 0 && Quantity::isPositive($line['quantity'])) {
                            $taxRate = 0;
                            if ($product = Product::withTrashed()->find($line['id'])) {
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

                            $invoiceLine = $invoice->invoiceLines()->create([
                                'product_id' => $line['id'],
                                'product_variation_id' => $this->variationIdFor($line['id'], $line['product_variation_id'] ?? null),
                                'quantity' => $line['quantity'],
                                'price' => $line['unit_price'],
                                ...$this->lineValues($line, $taxRate),
                                'tax_rate' => $taxRate ?? 0,
                                'currency' => $request->currency,
                                'comments' => $line['comments'],
                                'order' => $invoiceLineOrder,
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

            $this->syncTotals($invoice, $settled ? $linesBefore : null);

            return $invoice;
        });
    }

    /**
     * Push a newly created invoice to Xero, when it is connected.
     */
    protected function pushToXero(Invoice $invoice): void
    {
        if (XeroIntegration::connected()) {
            $lineItems = [];

            foreach ($invoice->invoiceLines as $line) {
                $lineItems[] = [
                    'Description' => XeroLineItem::description($line),
                    'Quantity' => $line->quantity,
                    'UnitAmount' => $line->price / 100,
                    'TaxType' => 'OUTPUT',
                    /* 'TaxAmount' => ($line->tax_total->value / 100), */
                    // 'LineAmount' => null,
                    'ItemCode' => XeroLineItem::itemCode($line),
                    'AccountCode' => $line->product->sales_account ?? 200,
                ];
            }

            try {
                $xeroInvoice = Xero::invoices()->store([
                    'Type' => 'ACCREC',
                    'Status' => 'AUTHORISED',
                    'Contact' => [
                        'ContactID' => $invoice->organization->xeroContact->contact_id,
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
    }

    /**
     * Set the header totals from the lines actually saved. The request's
     * totals are only what the form last summed, and can count a row the
     * loop above dropped. Amount due follows the total, less what has been
     * paid.
     *
     * Once a payment is recorded the total is fixed, as the form already has
     * it (Edit is hidden once anything is paid) - for the API and anything
     * else that edits by URL. Moving the total under a payment would leave
     * the invoice part-paid with no way to close it, or reopen a paid one and
     * lose the date it was paid on, which the dashboard reports from. An edit
     * that would move the subtotal or the tax - not only their sum, or the
     * header could carry tax no line does - is refused whole. One that
     * leaves them saves, and leaves the stored totals as they were too: an
     * older invoice's header can disagree with its lines by a cent of tax,
     * and a paid balance is not the place to correct that.
     *
     * @param  array{subtotal: int, tax: int}|null  $linesBefore  what the lines came to before the edit of a paid invoice; null otherwise
     */
    protected function syncTotals(Invoice $invoice, ?array $linesBefore = null): void
    {
        $totals = DocumentTotals::fromLines($invoice->invoiceLines()->get());
        $total = $totals['subtotal'] + $totals['tax'];
        $paid = (int) $invoice->getRawOriginal('amount_paid');

        if ($linesBefore !== null) {
            if ($totals !== $linesBefore) {
                throw ValidationException::withMessages([
                    'line_items' => ucfirst(__('laravel-crm::lang.invoice_total_locked_after_payment')),
                ]);
            }

            return;
        }

        $invoice->update([
            'subtotal' => $totals['subtotal'] / 100,
            'tax' => $totals['tax'] / 100,
            'total' => $total / 100,
            // A paid invoice owes nothing, whatever it recorded as paid. The
            // balance never goes below zero: the model's accessor reads a
            // negative one as the whole total still due.
            'amount_due' => ($invoice->fully_paid_at ? 0 : max(0, $total - $paid)) / 100,
        ]);
    }

    /**
     * Hold the invoice's row for the rest of the open transaction, and load
     * the row as the lock read it into $invoice.
     *
     * Call it as the first statement of the outermost transaction, before
     * any plain read. On MySQL/InnoDB a transaction's snapshot is fixed by
     * its first plain SELECT: a later SELECT ... FOR UPDATE reads the newest
     * row, but plain reads after it still see the snapshot. So the locked
     * row's own attributes are used - re-reading it would see the balance
     * from before a payment that landed while this waited - and anything
     * read after the lock sees that payment too.
     *
     * Without global scopes, as the model was loaded: the lock is about
     * concurrency, not tenancy, and a user who switched team in another tab
     * should not find the invoice they have open vanish mid-save.
     */
    public function lockForEdit(Invoice $invoice): Invoice
    {
        $locked = Invoice::withoutGlobalScopes()
            ->whereKey($invoice->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        $invoice->setRawAttributes($locked->getAttributes(), true);
        $invoice->unsetRelation('invoiceLines');

        return $invoice;
    }

    /**
     * Whether anything has been paid against the invoice, or it is marked
     * paid - the point from which its total is fixed.
     */
    protected function isSettled(Invoice $invoice): bool
    {
        return (int) $invoice->getRawOriginal('amount_paid') > 0 || $invoice->fully_paid_at !== null;
    }

    /**
     * Whether a line was re-sent at the product, price and quantity it was
     * saved with, so its saved amount and tax still stand.
     */
    protected function lineUnchanged(array $line, InvoiceLine $invoiceLine): bool
    {
        return (string) $line['id'] === (string) $invoiceLine->product_id
            && Money::toInteger($line['unit_price'] ?? null) === (int) $invoiceLine->getRawOriginal('price')
            && Quantity::equals($line['quantity'], $invoiceLine->quantity);
    }

    /**
     * A line's amount and tax, priced from its unit price and quantity rather
     * than taken from the request. The posted amount is only what the form
     * last showed - a follow-up invoice from an order used to carry the order
     * line's full amount against the part quantity still outstanding.
     *
     * In dollars, as the line mutators take them.
     *
     * @return array{amount: float, tax_amount: float}
     */
    private function lineValues(array $line, $taxRate): array
    {
        $amount = LineAmount::amountCents(Money::toInteger($line['unit_price'] ?? null) ?? 0, $line['quantity']);

        return [
            'amount' => $amount / 100,
            'tax_amount' => LineAmount::taxCents($amount, $taxRate ?? 0) / 100,
        ];
    }

    protected function addProduct($product, $request)
    {
        $newProduct = Product::create([
            'name' => $product['id'],
            'user_owner_id' => $request->user_owner_id ?? auth()->user()->id,
        ]);

        $newProduct->productPrices()->create([
            // The form sends unit_price; the older callers sent price.
            'unit_price' => $product['unit_price'] ?? $product['price'] ?? null,
            'currency' => $request->currency,
        ]);

        return $newProduct;
    }
}
