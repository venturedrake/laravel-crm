<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Support\LineAmount;
use VentureDrake\LaravelCrm\Support\Money;
use VentureDrake\LaravelCrm\Support\Quantity;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasDecimalQuantity;
use VentureDrake\LaravelCrm\Traits\HasProductLineName;

class OrderProduct extends Model
{
    use BelongsToTeams;
    use HasDecimalQuantity;
    use HasProductLineName;
    use SoftDeletes;

    protected $guarded = ['id'];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'order_products';
    }

    public function setPriceAttribute($value)
    {
        $this->attributes['price'] = Money::toInteger($value);
    }

    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = Money::toInteger($value);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class)->withTrashed();
    }

    public function invoiceLines()
    {
        return $this->hasMany(InvoiceLine::class);
    }

    /**
     * What is left of this line to invoice: its quantity, amount and tax
     * less what invoices already bill for it. Amount and tax in cents.
     *
     * A line on a soft-deleted invoice does not count, as in
     * QuantityWithinRemaining.
     *
     * @return array{quantity: float, amount: int, tax: int}
     */
    public function invoiceRemainder(?int $exceptInvoiceId = null): array
    {
        return $this->remainderAfter($this->invoicedLines($exceptInvoiceId));
    }

    /**
     * @return array{quantity: float, amount: int, tax: int}
     */
    protected function remainderAfter(Collection $invoiced): array
    {
        return [
            'quantity' => Quantity::round(Quantity::toFloat($this->quantity) - $invoiced->sum(fn ($line) => Quantity::toFloat($line->quantity))),
            'amount' => (int) $this->getRawOriginal('amount') - (int) $invoiced->sum(fn ($line) => (int) $line->getRawOriginal('amount')),
            'tax' => (int) $this->getRawOriginal('tax_amount') - (int) $invoiced->sum(fn ($line) => (int) $line->getRawOriginal('tax_amount')),
        ];
    }

    /**
     * Amount and tax in cents for an invoice line billing $quantity of this
     * line at $priceCents and $taxRate.
     *
     * Each invoice rounds its own share to the cent, so split across
     * invoices the shares can come to a cent more or less than the order
     * line - 2.5 x 333.33 is 833.33, but two invoices of 1.25 come to
     * 416.66 each. The invoice that takes the last of the quantity bills
     * exactly what is left instead, so the invoices add up to the order.
     *
     * Only when every invoice involved bills at the order line's price and
     * tax rate: then the difference is rounding alone. A line repriced on
     * its invoice is priced on its own.
     *
     * @return array{amount: int, tax: int}
     */
    public function invoiceLineCents($quantity, int $priceCents, $taxRate, ?int $exceptInvoiceId = null): array
    {
        $amount = LineAmount::amountCents($priceCents, $quantity);
        $line = ['amount' => $amount, 'tax' => LineAmount::taxCents($amount, $taxRate ?? 0)];

        if (! $this->atOrderPricing($priceCents, $taxRate)) {
            return $line;
        }

        $invoiced = $this->invoicedLines($exceptInvoiceId);

        if ($invoiced->contains(fn ($invoiceLine) => ! $this->atOrderPricing((int) $invoiceLine->getRawOriginal('price'), $invoiceLine->tax_rate))) {
            return $line;
        }

        $remainder = $this->remainderAfter($invoiced);

        if (! Quantity::isPositive($remainder['quantity']) || ! Quantity::equals($quantity, $remainder['quantity'])) {
            return $line;
        }

        return ['amount' => $remainder['amount'], 'tax' => $remainder['tax']];
    }

    /**
     * The lines already invoiced against this one, on invoices that are not
     * soft deleted.
     */
    protected function invoicedLines(?int $exceptInvoiceId = null): Collection
    {
        return $this->invoiceLines()
            ->whereHas('invoice')
            ->when($exceptInvoiceId, fn ($query, $invoiceId) => $query->where('invoice_id', '!=', $invoiceId))
            ->get(['id', 'invoice_id', 'quantity', 'price', 'amount', 'tax_rate', 'tax_amount']);
    }

    protected function atOrderPricing(int $priceCents, $taxRate): bool
    {
        return $priceCents === (int) $this->getRawOriginal('price')
            && abs((float) $taxRate - (float) $this->tax_rate) < 0.0001;
    }
}
